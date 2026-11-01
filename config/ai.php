<?php

return [
    // Interpreter Python yang sudah terpasang dependensi ml/requirements.txt
    'python' => env('AI_PYTHON_BIN', 'python'),

    'script' => base_path('ml/forecast.py'),

    // Hasil ml/backtest.py per gardu, ditampilkan sebagai keandalan di halaman
    'backtest' => base_path('ml/backtest_results.csv'),

    'timeout' => (int) env('AI_FORECAST_TIMEOUT', 300),

    // Kode gardu tempat model dilatih dan divalidasi. Gardu lain tetap diprediksi
    // dengan model berfitur R saja, tetapi ditandai belum divalidasi.
    'validated_gardu' => ['CJT'],

    'horizons' => [1, 7, 30],

    // Riwayat harian yang dikirim ke model (lookback terbesar + 1 hari + toleransi gap)
    'history_days' => 130,
];
