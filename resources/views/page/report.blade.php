@extends('layouts.master')

@section('title', 'Report - '.$resor->nama)

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

    <div class="row g-4">
        @forelse ($reportCards as $card)
            @php
                $g = $card['gardu'];
                $remaining = $card['remaining_days'];
                $remainingInt = is_null($remaining) ? null : (int) $remaining;
                $remainingClass = is_null($remainingInt) ? 'secondary' : ($remainingInt < 90 ? 'danger' : ($remainingInt < 180 ? 'warning' : 'success'));
                $fmt = fn ($v, $unit = '') => is_null($v) ? '-' : number_format((float) $v, 2).($unit ? ' '.$unit : '');
                $fmtDate = fn ($v) => is_null($v) ? '-' : \Carbon\Carbon::parse($v)->format('d/m/Y');

                $chartLabels = ['Temp', 'Volt Cell', 'THD', 'SOC', 'SOH', 'Volt Charge', 'Curr Charge'];
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
                            <i class="fas fa-hourglass-half me-1"></i> Sisa Umur Baterai: {{ is_null($remainingInt) ? '-' : $remainingInt }}
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

                        <div class="chart-wrap mb-3">
                            <canvas id="minMaxChart{{ $g->id }}"></canvas>
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
                                        <td class="text-start"><i class="fas fa-wave-square text-info me-2"></i>THD</td>
                                        <td>{{ $fmtDate($card['min_thd_at']) }}</td>
                                        <td>{{ $fmt($card['min_thd'], '%') }}</td>
                                        <td>{{ $fmtDate($card['max_thd_at']) }}</td>
                                        <td>{{ $fmt($card['max_thd'], '%') }}</td>
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

@media print {
    .sidebar,
    .main-header,
    .report-filter-card,
    .btn,
    .page-header,
    .footer {
        display: none !important;
    }

    .main-panel,
    .container,
    .page-inner {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
    }

    .report-card {
        break-inside: avoid;
        box-shadow: none !important;
        border: 1px solid #ddd !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined' || !window.reportChartData) {
        return;
    }

    Object.keys(window.reportChartData).forEach(function (garduId) {
        var cfg = window.reportChartData[garduId];
        var ctx = document.getElementById('minMaxChart' + garduId);
        if (!ctx) return;

        new Chart(ctx, {
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
                            font: { size: 10 }
                        }
                    }
                },
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        boxWidth: 8,
                        fontSize: 10
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            maxRotation: 0,
                            autoSkip: false,
                            font: { size: 10 }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: { font: { size: 10 } }
                    },
                    xAxes: [{
                        gridLines: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            maxRotation: 0,
                            autoSkip: false,
                            fontSize: 10
                        }
                    }],
                    yAxes: [{
                        gridLines: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            beginAtZero: true,
                            fontSize: 10
                        }
                    }]
                }
            }
        });
    });
});
</script>
@endsection
