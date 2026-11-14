<?php

return [
    // Jumlah sampel berturut-turut yang harus melanggar sebelum log dibuat atau naik ke alarm,
    // dan yang harus normal sebelum log dinyatakan pulih. 1 berarti langsung.
    'debounce_samples' => max(1, (int) env('ALARM_DEBOUNCE_SAMPLES', 3)),

    // Margin hysteresis dalam persen dari batas warning. Nilai baru dianggap normal setelah
    // melewati batas sejauh margin ini, agar log tidak muncul-hilang di sekitar batas.
    'hysteresis_pct' => (float) env('ALARM_HYSTERESIS_PCT', 2),

    // Estimasi waktu (ETA) dari tren linear data monitoring terbaru
    'eta' => [
        'min_points' => 5,
        // Tren dengan R² di bawah ini dianggap terlalu acak untuk diperkirakan
        'min_r2' => 0.5,
        // ETA lebih jauh dari ini ditampilkan sebagai "lebih dari N hari"
        'max_days' => 365,
        // Panjang data yang dipakai per metrik (jam). Lebih dari 24 jam dirata-rata per jam.
        'window_hours' => [
            'default' => 6,
            'thd' => 168,
            'soh' => 720,
        ],
    ],

    // Rentang fisik yang masih masuk akal (inklusif). Nilai di luar rentang dianggap sensor
    // rusak/terputus: dicatat sebagai sensor_fault, tidak memicu atau memulihkan warning/alarm,
    // dan tidak ikut agregasi harian untuk AI.
    'valid_ranges' => [
        'battery' => [
            'volt' => ['min' => 1, 'max' => 20, 'label' => 'Voltage', 'unit' => 'V'],
            'temp' => ['min' => -20, 'max' => 80, 'label' => 'Temperature', 'unit' => '°C'],
            'thd' => ['min' => 0.1, 'max' => 500, 'label' => 'IR', 'unit' => 'mΩ'],
            'soc' => ['min' => 0, 'max' => 100, 'label' => 'SOC', 'unit' => '%'],
            'soh' => ['min' => 0, 'max' => 100, 'label' => 'SOH', 'unit' => '%'],
        ],
        'charger' => [
            'voltage' => ['min' => 1, 'max' => 400, 'label' => 'Charger Voltage', 'unit' => 'V'],
            'current' => ['min' => -500, 'max' => 500, 'label' => 'Charger Current', 'unit' => 'A'],
        ],
    ],
];
