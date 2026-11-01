"""Uji golden: forecast.py harus mengulang prediksi test tesis (pred_test_tuned.csv).

Jalankan setelah memasang dependensi atau memindahkan model ke server:
  python ml/verify.py
"""
import json
import sys

from forecast import HERE, forecast, load_manifest

TOLERANCE = 1e-4    # mΩ


def main():
    golden = json.loads((HERE / "golden.json").read_text(encoding="utf-8"))
    result = forecast(golden["cells"], load_manifest())
    got = {(f["battery_id"], f["horizon"]): f["r_pred"] for f in result["forecasts"]}
    worst = 0.0
    for row in golden["expected"]:
        worst = max(worst, abs(got[(row["battery_id"], row["horizon"])] - row["r_pred"]))
    print(f"{len(golden['expected'])} prediksi dibandingkan, selisih maksimum {worst:.2e} mΩ")
    return 0 if worst <= TOLERANCE else 1


if __name__ == "__main__":
    sys.exit(main())
