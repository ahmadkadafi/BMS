@extends('layouts.master')

@section('title', 'Report - '.($resor?->nama ?? 'Semua Data'))

@section('content')
<div class="page-inner">
    <div class="card mb-4 report-filter-card">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-bold">Bulan</label>
                    <select name="month" class="form-select">
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" @selected($month === $m)>
                                {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Tahun</label>
                    <select name="year" class="form-select">
                        @foreach ($years as $y)
                            <option value="{{ $y }}" @selected($year === $y)>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary w-100"><i class="fas fa-filter"></i> Tampilkan Report</button>
                </div>
                <div class="col-md-3">
                    <button type="button" class="btn btn-outline-dark w-100" onclick="window.print()">
                        <i class="fas fa-print"></i> Cetak PDF
                    </button>
                </div>
            </form>
        </div>
    </div>

    @php
        $periodLabel = \Carbon\Carbon::create($year, $month, 1)->translatedFormat('F Y');
    @endphp
    <div class="report-print-header">
        <h3>Laporan Bulanan Battery Management System</h3>
        <table>
            <tr><td>Resor</td><td>: {{ $resor?->nama ?? 'Semua Resor' }}</td></tr>
            <tr><td>Periode</td><td>: {{ $periodLabel }}</td></tr>
            <tr><td>Jumlah Gardu</td><td>: {{ $reportCards->count() }}</td></tr>
            <tr><td>Dicetak</td><td>: {{ now()->format('d/m/Y H:i') }}</td></tr>
        </table>
    </div>

    <div class="row g-4 report-grid">
        @forelse ($reportCards as $card)
            @php
                $g = $card['gardu'];
                $remaining = $card['remaining_days'];
                $remainingInt = is_null($remaining) ? null : (int) $remaining;
                $remainingClass = is_null($remainingInt) ? 'secondary' : ($remainingInt < 90 ? 'danger' : ($remainingInt < 180 ? 'warning' : 'success'));
                $fmt = fn ($v, $unit = '') => is_null($v) ? '-' : number_format((float) $v, 2).($unit ? ' '.$unit : '');
                $fmtDate = fn ($v) => is_null($v) ? '-' : \Carbon\Carbon::parse($v)->format('d/m/Y');

                $chartLabels = ['Temp', 'Volt Cell', 'IR', 'SOC', 'SOH', 'Volt Charge', 'Curr Charge'];
                $chartMin = [
                    $card['min_temp'],
                    $card['min_volt_cell'],
                    $card['min_thd'],
                    $card['min_soc'],
                    $card['min_soh'],
                    $card['min_voltage_charge'],
                    $card['min_current_charge'],
                ];
                $chartMax = [
                    $card['max_temp'],
                    $card['max_volt_cell'],
                    $card['max_thd'],
                    $card['max_soc'],
                    $card['max_soh'],
                    $card['max_voltage_charge'],
                    $card['max_current_charge'],
                ];
            @endphp
            <div class="col-12 col-xl-6">
                <div class="card report-card border-0 shadow-sm h-100">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="mb-0 fw-bold"><i class="fas fa-bolt text-primary me-2"></i>{{ $g->nama }}</h5>
                            <small class="text-muted">Kode: {{ $g->kode }} | Bulan {{ str_pad($month, 2, '0', STR_PAD_LEFT) }}/{{ $year }}</small>
                        </div>
                        <span class="badge bg-{{ $remainingClass }} px-3 py-2">
                            <i class="fas fa-hourglass-half me-1"></i> Sisa Umur Baterai: {{ is_null($remainingInt) ? '-' : $remainingInt.' hari' }}
                        </span>
                    </div>

                    <div class="card-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <div class="metric-tile warning-tile">
                                    <div class="icon-wrap"><i class="fas fa-exclamation-triangle"></i></div>
                                    <div>
                                        <div class="label">Jumlah Warning</div>
                                        <div class="value">{{ $card['warning_count'] }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="metric-tile alarm-tile">
                                    <div class="icon-wrap"><i class="fas fa-bell"></i></div>
                                    <div>
                                        <div class="label">Jumlah Alarm</div>
                                        <div class="value">{{ $card['alarm_count'] }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="report-body">
                        <div class="chart-wrap mb-3">
                            <canvas id="minMaxChart{{ $g->id }}"></canvas>
                            {{-- Salinan gambar grafik untuk dicetak; canvas tidak menyesuaikan ukuran kertas --}}
                            <img class="chart-print-img" id="minMaxChartImg{{ $g->id }}" alt="Grafik min/max {{ $g->nama }}">
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered align-middle text-center mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Variabel</th>
                                        <th>Date Min</th>
                                        <th>Value Min</th>
                                        <th>Date Max</th>
                                        <th>Value Max</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="text-start"><i class="fas fa-thermometer-half text-danger me-2"></i>Temperature</td>
                                        <td>{{ $fmtDate($card['min_temp_at']) }}</td>
                                        <td>{{ $fmt($card['min_temp'], '°C') }}</td>
                                        <td>{{ $fmtDate($card['max_temp_at']) }}</td>
                                        <td>{{ $fmt($card['max_temp'], '°C') }}</td>    
                                    </tr>
                                    <tr>
                                        <td class="text-start"><i class="fas fa-car-battery text-success me-2"></i>Voltage Cell</td>
                                        <td>{{ $fmtDate($card['min_volt_cell_at']) }}</td>
                                        <td>{{ $fmt($card['min_volt_cell'], 'V') }}</td>
                                        <td>{{ $fmtDate($card['max_volt_cell_at']) }}</td>
                                        <td>{{ $fmt($card['max_volt_cell'], 'V') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-start"><i class="fas fa-tachometer-alt text-info me-2"></i>IR</td>
                                        <td>{{ $fmtDate($card['min_thd_at']) }}</td>
                                        <td>{{ $fmt($card['min_thd'], 'mΩ') }}</td>
                                        <td>{{ $fmtDate($card['max_thd_at']) }}</td>
                                        <td>{{ $fmt($card['max_thd'], 'mΩ') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-start"><i class="fas fa-chart-line text-primary me-2"></i>SOC</td>
                                        <td>{{ $fmtDate($card['min_soc_at']) }}</td>
                                        <td>{{ $fmt($card['min_soc'], '%') }}</td>
                                        <td>{{ $fmtDate($card['max_soc_at']) }}</td>
                                        <td>{{ $fmt($card['max_soc'], '%') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-start"><i class="fas fa-heartbeat text-warning me-2"></i>SOH</td>
                                        <td>{{ $fmtDate($card['min_soh_at']) }}</td>
                                        <td>{{ $fmt($card['min_soh'], '%') }}</td>
                                        <td>{{ $fmtDate($card['max_soh_at']) }}</td>
                                        <td>{{ $fmt($card['max_soh'], '%') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-start"><i class="fas fa-charging-station text-primary me-2"></i>Voltage Charge</td>
                                        <td>{{ $fmtDate($card['min_voltage_charge_at']) }}</td>
                                        <td>{{ $fmt($card['min_voltage_charge'], 'V') }}</td>
                                        <td>{{ $fmtDate($card['max_voltage_charge_at']) }}</td>
                                        <td>{{ $fmt($card['max_voltage_charge'], 'V') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-start"><i class="fas fa-bolt text-warning me-2"></i>Current Charge</td>
                                        <td>{{ $fmtDate($card['min_current_charge_at']) }}</td>
                                        <td>{{ $fmt($card['min_current_charge'], 'A') }}</td>
                                        <td>{{ $fmtDate($card['max_current_charge_at']) }}</td>
                                        <td>{{ $fmt($card['max_current_charge'], 'A') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                window.reportChartData = window.reportChartData || {};
                window.reportChartData[{{ $g->id }}] = {
                    labels: @json($chartLabels),
                    min: @json($chartMin),
                    max: @json($chartMax)
                };
            </script>
        @empty
            <div class="col-12">
                <div class="alert alert-info mb-0">Belum ada data gardu untuk resor ini.</div>
            </div>
        @endforelse
    </div>
</div>
@endsection

@section('adding')
<style>
.report-filter-card {
    border: 0;
    border-radius: 14px;
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.06);
}

.report-card .card-header {
    background: linear-gradient(120deg, #f6f9ff 0%, #eef3ff 100%);
}

.chart-wrap {
    background: linear-gradient(180deg, #fcfdff 0%, #f6f9ff 100%);
    border: 1px solid #e7eefb;
    border-radius: 12px;
    padding: 10px;
    height: 250px;
}

.metric-tile {
    border-radius: 12px;
    padding: 12px 14px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.metric-tile .icon-wrap {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

.metric-tile .label {
    font-size: 13px;
    color: #6b7280;
}

.metric-tile .value {
    font-size: 24px;
    font-weight: 700;
    line-height: 1;
}

.warning-tile {
    background: #fff8e6;
    border: 1px solid #ffe4a6;
}

.warning-tile .icon-wrap {
    background: #ffe8b8;
    color: #9a6700;
}

.alarm-tile {
    background: #fff0f2;
    border: 1px solid #ffd1d8;
}

.alarm-tile .icon-wrap {
    background: #ffd7de;
    color: #b42318;
}

/* Hanya tampil saat dicetak */
.report-print-header,
.chart-print-img {
    display: none;
}

@page {
    size: A4 portrait;
    margin: 12mm 10mm;
}

@media print {
    /* Template membuat wrapper setinggi layar dengan overflow hidden; tanpa ini hasil cetak terpotong */
    html,
    body,
    .wrapper,
    .main-panel,
    .main-panel > .container {
        position: static !important;
        float: none !important;
        width: 100% !important;
        max-width: 100% !important;
        height: auto !important;
        min-height: 0 !important;
        margin: 0 !important;
        overflow: visible !important;
        transform: none !important;
    }

    body {
        background: #fff !important;
        font-size: 11px;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .sidebar,
    .main-header,
    .footer,
    .report-filter-card,
    .btn {
        display: none !important;
    }

    .page-inner {
        padding: 0 !important;
        margin: 0 !important;
    }

    .report-print-header {
        display: block;
        border-bottom: 2px solid #1f3a8a;
        padding-bottom: 6px;
        margin-bottom: 10px;
    }

    .report-print-header h3 {
        font-size: 16px;
        font-weight: 700;
        margin: 0 0 6px;
        color: #1f3a8a;
    }

    .report-print-header table td {
        padding: 0 8px 0 0;
        font-size: 11px;
    }

    .report-grid {
        margin: 0 !important;
        --bs-gutter-x: 0;
        --bs-gutter-y: 0;
    }

    .report-grid > [class*="col-"] {
        flex: 0 0 100%;
        width: 100% !important;
        max-width: 100% !important;
        padding: 0 !important;
    }

    .report-card {
        height: auto !important;
        margin-bottom: 5mm;
        border: 1px solid #cfd8e3 !important;
        border-radius: 8px !important;
        box-shadow: none !important;
        break-inside: avoid;
        page-break-inside: avoid;
    }

    .report-card .card-header {
        padding: 6px 12px !important;
    }

    .report-card .card-header h5 {
        font-size: 14px;
    }

    .report-card .card-body {
        padding: 8px 12px !important;
    }

    .report-card .row.g-3 {
        --bs-gutter-y: 0;
        margin-bottom: 6px !important;
    }

    .report-card .row.g-3 > .col-md-6 {
        flex: 0 0 50%;
        max-width: 50%;
    }

    .metric-tile {
        padding: 4px 10px;
        gap: 8px;
    }

    .metric-tile .icon-wrap {
        width: 26px;
        height: 26px;
        font-size: 12px;
    }

    .metric-tile .value {
        font-size: 15px;
    }

    .chart-wrap {
        height: auto;
        padding: 4px;
        margin-bottom: 6px !important;
    }

    /* Grafik di kiri, tabel di kanan agar 2-3 gardu muat per halaman */
    .report-body {
        display: grid;
        grid-template-columns: 42% 1fr;
        gap: 8px;
        align-items: center;
    }

    .report-body .chart-wrap {
        margin-bottom: 0 !important;
    }

    /* Pakai gambar cetak jika sudah tersedia; jika belum, canvas tetap dicetak */
    .chart-wrap:has(.chart-print-img[src]) canvas {
        display: none !important;
    }

    .chart-print-img[src] {
        display: block;
        width: 100%;
        height: auto;
    }

    .table-responsive {
        overflow: visible !important;
    }

    /* Selector lebih spesifik dari template yang memaksa padding 16px 24px */
    .report-card .table > thead > tr > th,
    .report-card .table > tbody > tr > td {
        padding: 3px 8px !important;
        font-size: 10px !important;
        letter-spacing: 0;
        line-height: 1.3;
        white-space: nowrap;
        height: auto !important;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
function reportChartConfig(cfg, fontSize) {
    return {
        type: 'bar',
        data: {
            labels: cfg.labels,
            datasets: [
                {
                    label: 'Minimum',
                    data: cfg.min,
                    backgroundColor: 'rgba(255, 159, 64, 0.75)',
                    borderColor: 'rgba(255, 159, 64, 1)',
                    borderWidth: 1,
                    borderRadius: 6
                },
                {
                    label: 'Maksimum',
                    data: cfg.max,
                    backgroundColor: 'rgba(54, 162, 235, 0.75)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1,
                    borderRadius: 6
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        pointStyle: 'rectRounded',
                        boxWidth: 8,
                        boxHeight: 8,
                        padding: 10,
                        font: { size: fontSize }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        maxRotation: 0,
                        autoSkip: false,
                        font: { size: fontSize }
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: { display: false },
                    ticks: { font: { size: fontSize } }
                }
            }
        }
    };
}

// Gambar untuk cetak dirender sekali dengan ukuran tetap, tidak bergantung lebar layar
function renderPrintImage(cfg, img) {
    var canvas = document.createElement('canvas');
    canvas.width = 560;
    canvas.height = 380;

    var config = reportChartConfig(cfg, 13);
    config.options.responsive = false;
    config.options.animation = false;
    config.options.devicePixelRatio = 2;

    var chart = new Chart(canvas, config);
    img.src = chart.toBase64Image('image/png', 1);
    chart.destroy();
}

document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined' || !window.reportChartData) {
        return;
    }

    Object.keys(window.reportChartData).forEach(function (garduId) {
        var cfg = window.reportChartData[garduId];
        var ctx = document.getElementById('minMaxChart' + garduId);
        var img = document.getElementById('minMaxChartImg' + garduId);
        if (!ctx) return;

        new Chart(ctx, reportChartConfig(cfg, 10));

        if (img) {
            renderPrintImage(cfg, img);
        }
    });
});

// Nama file PDF bawaan browser diambil dari judul halaman
(function () {
    var originalTitle = document.title;
    var printTitle = @json('Report BMS - '.($resor?->nama ?? 'Semua Resor').' - '.sprintf('%04d-%02d', $year, $month));
    window.addEventListener('beforeprint', function () { document.title = printTitle; });
    window.addEventListener('afterprint', function () { document.title = originalTitle; });
})();
</script>
@endsection
