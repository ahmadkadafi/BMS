"""Bangun tabel harian satu gardu dari CSV mentah, setara tahap 0 dan 2.1 tesis.

  python ml/build_daily.py "<Dataset 2>/Lebak" lebak_daily.csv

Jenis file dikenali dari header karena penamaan file tiap gardu berbeda. File gabungan
berawalan 00_, 01_, 02_ dilewati. Nilai '-' dan IR <= 0 (sensor mati) dianggap kosong.
"""
import re
import sys
from pathlib import Path

import pandas as pd

FREQ = "30min"
COMBINED_PREFIX = re.compile(r"^0\d_")


def kind_of(columns):
    if "#1_Resistance" in columns:
        return "R"
    if "#1_Temperature" in columns:
        return "T"
    if "#1_Voltage" in columns:
        return "V"
    if "Voltage" in columns:
        return "V_charger"
    if "Current" in columns:
        return "I_charger"
    return None


def load(path):
    df = pd.read_csv(path, encoding="utf-8-sig", skip_blank_lines=True)
    df.columns = [c.strip() for c in df.columns]
    kind = kind_of(df.columns)
    if kind is None:
        return None, None
    df = df.dropna(how="all")
    df.index = pd.to_datetime(df["Date"].astype(str) + " " + df["Time"].astype(str), errors="coerce").dt.round(FREQ)
    df = df[df.index.notna()].drop(columns=["Date", "Time", "String"], errors="ignore")
    df = df.apply(pd.to_numeric, errors="coerce")
    df.columns = [int(re.match(r"#(\d+)_", c).group(1)) if c.startswith("#") else kind for c in df.columns]
    return kind, df


def build(folder):
    tables = {}
    for path in sorted(Path(folder).glob("*.csv")):
        if COMBINED_PREFIX.match(path.name):
            continue
        kind, df = load(path)
        if kind:
            tables.setdefault(kind, []).append(df)
    tables = {k: pd.concat(v).groupby(level=0).mean() for k, v in tables.items()}

    resistance = tables["R"].where(tables["R"] > 0)
    shared = pd.DataFrame({
        "V_charger": tables["V_charger"]["V_charger"].resample("D").mean(),
        "I_mean": tables["I_charger"]["I_charger"].resample("D").mean(),
        "I_min": tables["I_charger"]["I_charger"].resample("D").min(),
    })

    parts = []
    for cell in sorted(resistance.columns):
        d = pd.DataFrame({
            "R": resistance[cell].resample("D").last(),
            "V": tables["V"][cell].resample("D").mean(),
            "T": tables["T"][cell].resample("D").mean(),
        }).join(shared)
        d["dR1"] = d["R"].diff()
        d.insert(0, "cell_id", cell)
        parts.append(d)

    daily = pd.concat(parts).rename_axis("date").reset_index()
    return daily.dropna(subset=["R", "V", "T"])


if __name__ == "__main__":
    build(sys.argv[1]).to_csv(sys.argv[2], index=False)
