# Forecast Internal Resistance

Menerapkan model tesis (LSTM dan SimpleRNN hasil tuning, ensemble 5 seed) pada data BMS.

## Alur

```
battery_monitoring + charger_monitoring   tiap 5 menit
        | aggregateDaily()                IR terakhir, V dan T rata-rata, arus rata-rata dan minimum
        v
battery_daily                             setara daily_long.csv pada tahap 2
        | forecast.py                     window, scaler, 5 seed, kembali ke mΩ
        v
ir_forecast + ir_forecast_run             dibaca halaman AI Prediction
```

`php artisan ai:forecast` terjadwal tiap jam, tetapi hanya memanggil model jika ada hari lengkap
baru. IR diukur sekitar sekali sehari, jadi prediksi harian adalah resolusi yang sesuai dengan
pelatihan model.

## Pemasangan

```
pip install -r ml/requirements.txt
python ml/verify.py
php artisan migrate
```

Isi `AI_PYTHON_BIN` di `.env` jika Python tidak bernama `python`. `verify.py` harus keluar dengan
kode 0. Jika gagal, versi Keras/TensorFlow di server menghasilkan angka berbeda dari pelatihan.
Scheduler Laravel (`php artisan schedule:run` tiap menit) harus aktif.

## Model

| Horizon | Cilejit | Gardu lain | Riwayat minimum |
|---|---|---|---|
| H+1 | LSTM_h1 | SimpleRNN_h1 | 15 hari / 61 hari |
| H+7 | LSTM_h7 | LSTM_h7 | 61 hari |
| H+30 | LSTM_h30 | LSTM_h30 | 61 hari |

LSTM dipilih karena RMSE validasinya terendah di semua horizon. LSTM_h1 memakai V, T, dan arus
absolut sehingga tidak berlaku di gardu lain.

`manifest.json` berisi scaler tiap model. Scaler ini dihitung ulang dari `daily_long.csv` dengan
lookback dan fitur hasil tuning, karena `scaler_h*.json` tahap 2 hanya berlaku untuk lookback 30.
Hasilnya mengulang `pred_test_tuned.csv` dengan selisih di bawah 2e-7 mΩ.

## Kualitas prediksi

Prediksi tetap disimpan, tetapi ditandai pada kolom `quality`:

- `ir_frozen`: IR tidak berubah 7 hari atau lebih (maksimum di data latih 6 hari).
- `ood`: |z| input melebihi 12 (maksimum di data test 10,4).

Rentang ±1,96 × RMSE test hanya ditampilkan untuk Cilejit.

## Riwayat dan replay

Gardu yang belum punya riwayat berurutan cukup di `battery_monitoring` bisa diisi dari CSV mentah:

```
python ml/build_daily.py "<Dataset 2>/Lebak" lebak_daily.csv
php artisan ai:import-history lebak_daily.csv --gardu=LBK
php artisan ai:forecast --gardu=LBK --as-of=2026-02-28 --force
```

`build_daily.py` mereproduksi `daily_long.csv` Cilejit tanpa selisih.

## Keandalan per gardu

`backtest_results.csv` adalah hasil `backtest.py` pada periode target 28 Des 2025 - 28 Feb 2026,
dibandingkan dengan persistence. Kolom `skill_pct` positif berarti model lebih akurat. Halaman
menampilkan nilai ini per horizon. Jalankan ulang setelah model atau data riwayat berubah.
Backtest tidak memakai uji signifikansi dan hanya mencakup sekitar 63 hari.
