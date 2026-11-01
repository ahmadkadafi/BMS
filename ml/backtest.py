"""Uji model pada riwayat tiap gardu, memakai periode target yang sama dengan data test tesis.

  python ml/backtest.py hasil.csv lebak_daily.csv:LBK cilejit_daily.csv:CJT ...

Pembanding: persistence R(t+h) = R(t). Skill positif berarti model lebih baik dari persistence.
"""
import csv
import sys
from datetime import date, timedelta

import numpy as np
import pandas as pd

from forecast import FROZEN_DAYS, OOD_Z, build_window, fill_calendar, flat_run, load_manifest, predict

TEST_START = date(2025, 12, 28)
VALIDATED = {"CJT"}


def rows_of(group):
    return [{"date": str(r.date)[:10], "r": r.R, "v": r.V, "t": r.T, "v_charger": r.V_charger,
             "i_mean": r.I_mean, "i_min": r.I_min} for r in group.itertuples()]


def backtest_site(path, kode, manifest):
    daily = pd.read_csv(path)
    result = []
    for horizon, choice in manifest["horizons"].items():
        h = int(horizon)
        name = choice["validated" if kode in VALIDATED else "general"]
        spec = manifest["models"][name]
        windows, truth, anchors, flats = [], [], [], []
        for _, group in daily.groupby("cell_id"):
            filled, last = fill_calendar(rows_of(group))
            first = last - timedelta(days=len(filled) - 1)
            for i in range(spec["lookback"], len(filled) - h):
                target = first + timedelta(days=i + h)
                if target < TEST_START or filled[i] is None or filled[i + h] is None:
                    continue
                x = build_window(filled[:i + 1], spec)
                if x is None:
                    continue
                windows.append(x)
                truth.append(filled[i + h]["r"])
                anchors.append(filled[i]["r"])
                tail = [d["r"] for d in filled[max(0, i - FROZEN_DAYS - 1):i + 1] if d is not None]
                flats.append(flat_run(tail))
        if not windows:
            continue
        delta, _, max_z = predict(spec, name, np.stack(windows))
        anchors, truth = np.array(anchors), np.array(truth)
        ok = (np.array(flats) < FROZEN_DAYS) & (max_z <= OOD_Z)
        rmse = float(np.sqrt(np.mean((anchors[ok] + delta[ok] - truth[ok]) ** 2))) if ok.any() else None
        persist = float(np.sqrt(np.mean((anchors[ok] - truth[ok]) ** 2))) if ok.any() else None
        result.append({
            "gardu": kode, "h": h, "model": name, "n": len(truth), "n_ok": int(ok.sum()),
            "rmse": None if rmse is None else round(rmse, 4),
            "rmse_persistence": None if persist is None else round(persist, 4),
            "skill_pct": None if not persist else round(100 * (1 - rmse / persist), 1),
            "mean_abs_dR1": round(float(np.abs(np.diff(anchors)).mean()), 4),
        })
    return result


if __name__ == "__main__":
    manifest = load_manifest()
    rows = []
    for spec in sys.argv[2:]:
        path, kode = spec.rsplit(":", 1)
        rows.extend(backtest_site(path, kode, manifest))
    with open(sys.argv[1], "w", newline="", encoding="utf-8") as f:
        writer = csv.DictWriter(f, fieldnames=list(rows[0]))
        writer.writeheader()
        writer.writerows(rows)
