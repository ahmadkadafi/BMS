"""Forecast internal resistance sel VRLA dari model hasil tuning tesis.

Dipanggil oleh `php artisan ai:forecast`:
  stdin  : {"cells": [{"battery_id": 54, "validated": true, "anchor": "2026-08-06",
                       "days": [{"date": "2026-08-06", "r": 4.73, "v": 13.58, "t": 22.9,
                                 "v_charger": 122.8, "i_mean": 0.0, "i_min": 0.0}, ...]}]}
  stdout : {"forecasts": [...], "skipped": [...]}

`days` hanya berisi hari yang sudah lengkap. `anchor` adalah hari t milik gardu; sel yang berhenti
melapor sebelum anchor dilewati. Tanpa `anchor`, hari terakhir pada `days` dipakai.
Preprocessing di sini harus identik dengan tahap 2 dan 4 tesis; `verify.py` mengujinya.
"""
import json
import os
import sys
from datetime import date, timedelta
from pathlib import Path

os.environ.setdefault("KERAS_BACKEND", "tensorflow")
os.environ.setdefault("TF_CPP_MIN_LOG_LEVEL", "3")
os.environ.setdefault("TF_ENABLE_ONEDNN_OPTS", "0")

import numpy as np

HERE = Path(__file__).resolve().parent
MAX_GAP_DAYS = 3    # hari kosong yang boleh diisi nilai terakhir (IR bersifat sample-and-hold)
FROZEN_DAYS = 7     # IR terpanjang tidak berubah di data latih = 6 hari
OOD_Z = 12          # |z| terbesar pada data test = 10,4 (V_charger)
Z_95 = 1.96

COLUMNS = {"V": "v", "T": "t", "V_charger": "v_charger", "I_mean": "i_mean", "I_min": "i_min"}


def fill_calendar(days, anchor=None):
    """Susun deret kalender s/d anchor; hari kosong diisi nilai terakhir maks. MAX_GAP_DAYS."""
    by_date = {date.fromisoformat(d["date"]): d for d in days}
    first = min(by_date)
    last = date.fromisoformat(anchor) if anchor else max(by_date)
    filled, previous, gap = [], None, 0
    for n in range((last - first).days + 1):
        day = first + timedelta(days=n)
        if day in by_date:
            previous, gap = by_date[day], 0
        else:
            gap += 1
        filled.append(previous if gap <= MAX_GAP_DAYS else None)
    return filled, last


def flat_run(values):
    """Jumlah hari terakhir berturut-turut tanpa perubahan IR."""
    run = 0
    for current, before in zip(reversed(values[1:]), reversed(values[:-1])):
        if current != before:
            break
        run += 1
    return run


def consecutive_days(filled):
    """Jumlah hari berurutan yang terisi, dihitung mundur dari anchor."""
    count = 0
    for day in reversed(filled):
        if day is None:
            break
        count += 1
    return count


def build_window(filled, spec):
    """Window (lookback, n_fitur) sesuai tahap 2; None jika ada hari yang tidak terisi."""
    tail = filled[-(spec["lookback"] + 1):]
    if any(d is None for d in tail):
        return None
    r = np.array([d["r"] for d in tail], dtype=float)
    columns = []
    for feature in spec["features"]:
        if feature == "dR1":
            columns.append(np.diff(r))
        elif feature == "R_rel":
            columns.append(r[1:] - r[-1])
        else:
            raw = [d[COLUMNS[feature]] for d in tail[1:]]
            if any(v is None for v in raw):
                return None
            columns.append(np.array(raw, dtype=float))
    return np.column_stack(columns)


def predict(spec, name, x):
    """Rata-rata dan simpangan antar seed (mΩ), serta |z| terbesar tiap window."""
    import keras

    x_mean, x_std = np.array(spec["x_mean"]), np.array(spec["x_std"])
    scaled = ((x.astype(np.float32).astype(np.float64) - x_mean) / x_std).astype(np.float32)
    runs = []
    for seed in spec["seeds"]:
        model = keras.models.load_model(HERE / "models" / f"{name}_seed{seed}.keras", compile=False)
        runs.append(model.predict(scaled, verbose=0, batch_size=256).ravel())
    delta = np.array(runs, dtype=float) * spec["y_std"] + spec["y_mean"]
    return delta.mean(axis=0), delta.std(axis=0), np.abs(scaled).max(axis=(1, 2))


def forecast(cells, manifest):
    forecasts, skipped, pending = [], [], {}

    for cell in cells:
        filled, anchor = fill_calendar(cell["days"], cell.get("anchor"))
        r_series = [d["r"] for d in filled if d is not None]
        available = consecutive_days(filled)
        for horizon, choice in manifest["horizons"].items():
            name = choice["validated" if cell["validated"] else "general"]
            spec = manifest["models"][name]
            required = spec["lookback"] + 1
            base = {"battery_id": cell["battery_id"], "horizon": int(horizon),
                    "model": name, "required": required, "available": available}
            if available < required:
                skipped.append({**base, "reason": "insufficient_history"})
                continue
            x = build_window(filled, spec)
            if x is None:
                skipped.append({**base, "reason": "data_gap"})
                continue
            pending.setdefault(name, []).append({
                "cell": cell, "x": x, "horizon": int(horizon), "anchor": anchor,
                "r_anchor": r_series[-1], "flat_days": flat_run(r_series),
            })

    for name, items in pending.items():
        spec = manifest["models"][name]
        mean, std, max_z = predict(spec, name, np.stack([i["x"] for i in items]))
        for item, d_r, spread, z in zip(items, mean, std, max_z):
            quality = "ok"
            if item["flat_days"] >= FROZEN_DAYS:
                quality = "ir_frozen"
            elif z > OOD_Z:
                quality = "ood"
            r_pred = item["r_anchor"] + float(d_r)
            band = Z_95 * spec["rmse_test"] if item["cell"]["validated"] else None
            forecasts.append({
                "battery_id": item["cell"]["battery_id"],
                "horizon": item["horizon"],
                "model": name,
                "anchor_date": item["anchor"].isoformat(),
                "target_date": (item["anchor"] + timedelta(days=item["horizon"])).isoformat(),
                "r_anchor": item["r_anchor"],
                "r_pred": round(r_pred, 6),
                "r_low": None if band is None else round(r_pred - band, 6),
                "r_high": None if band is None else round(r_pred + band, 6),
                "seed_std": round(float(spread), 6),
                "flat_days": item["flat_days"],
                "max_z": round(float(z), 2),
                "quality": quality,
            })
    return {"forecasts": forecasts, "skipped": skipped}


def load_manifest():
    return json.loads((HERE / "manifest.json").read_text(encoding="utf-8"))


if __name__ == "__main__":
    payload = json.load(sys.stdin)
    json.dump(forecast(payload["cells"], load_manifest()), sys.stdout)
