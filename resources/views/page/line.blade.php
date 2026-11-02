@extends('layouts.master')

@section('title', 'Line - '.($resor?->nama ?? 'Semua Data'))

@section('content')
@php
    $chartOptions = [
        'volt' => 'Voltage Cell',
        'temp' => 'Temperature Cell',
        'thd' => 'THD Cell',
        'soc' => 'SOC Cell',
        'soh' => 'SOH Cell',
        'voltage_charger' => 'Voltage Charger',
        'current_charger' => 'Current Charger',
    ];
@endphp

<style>
    .line-filter-card {
        border: 0;
        border-radius: 16px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
    }
    .line-chart-card {
        border: 0;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 10px 26px rgba(0, 0, 0, 0.08);
    }
    .line-chart-head {
        background: linear-gradient(90deg, #f8fbff, #edf6ff);
        border-bottom: 1px solid #deebf8;
    }
    .chart-box {
        height: 420px;
    }
</style>

<div class="page-inner">
    <div class="card line-filter-card mb-4">
        <div class="card-body">
            <form method="GET" id="lineFilterForm">
                <div class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label fw-bold"><i class="fas fa-map-marker-alt text-primary mr-1"></i> Lokasi</label>
                        <select name="gardu_id" class="form-select">
                            @foreach ($gardu as $g)
                                <option value="{{ $g->id }}" {{ (int) $selectedGarduId === (int) $g->id ? 'selected' : '' }}>
                                    {{ $g->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold"><i class="fas fa-chart-line text-primary mr-1"></i> Line Chart</label>
                        <select name="chart" class="form-select">
                            @foreach ($chartOptions as $value => $label)
                                <option value="{{ $value }}" {{ $selectedChart === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fw-bold"><i class="fas fa-calendar-alt text-primary mr-1"></i> Dari Tanggal</label>
                        <input type="date" name="from" id="filterFrom" class="form-control" value="{{ $fromDate }}" max="{{ $maxDate }}" required>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fw-bold"><i class="fas fa-calendar-check text-primary mr-1"></i> Sampai Tanggal</label>
                        <input type="date" name="to" id="filterTo" class="form-control" value="{{ $toDate }}" max="{{ $maxDate }}" required>
                    </div>

                    <div class="col-md-3">
                        <button class="btn btn-primary w-100">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                    </div>
                </div>
                <small class="text-muted d-block mt-2">
                    <i class="fas fa-info-circle mr-1"></i> Rentang tanggal maksimal {{ \App\Http\Controllers\LineController::MAX_RANGE_DAYS }} hari.
                </small>
            </form>
        </div>
    </div>

    @if ($rangeNotice)
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle mr-1"></i> {{ $rangeNotice }}
        </div>
    @endif

    <div class="card line-chart-card mb-4">
        <div class="card-header line-chart-head d-flex justify-content-between align-items-center">
            <span class="fw-bold">
                <i class="fas fa-chart-line text-primary mr-1"></i> {{ $chartData['title'] }}
            </span>
            <small class="text-muted">
                {{ $gardu->firstWhere('id', $selectedGarduId)?->nama ?? '-' }} | {{ $fromDate }} s/d {{ $toDate }}
            </small>
        </div>
        <div class="card-body">
            @if (!empty($chartData['datasets']))
                <div class="chart-box">
                    <canvas id="lineChart"></canvas>
                </div>
            @else
                <div class="text-center text-muted py-5">
                    Tidak ada data untuk filter yang dipilih.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('adding')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<style>
.line-legend-note {
    font-size: 12px;
    color: #6c757d;
}
</style>

<script>
const maxRangeDays = @json(\App\Http\Controllers\LineController::MAX_RANGE_DAYS);
const chartLabels = @json($chartData['labels']);
const chartDatasets = @json($chartData['datasets']);
const chartUnit = @json($chartData['unit']);
const chartPalette = [
    '#0d6efd', '#dc3545', '#198754', '#fd7e14', '#6f42c1',
    '#20c997', '#e83e8c', '#0dcaf0', '#ffc107', '#6c757d',
    '#8b5cf6', '#14b8a6', '#ef4444', '#f59e0b', '#10b981'
];

function stylizeDatasets(datasets) {
    return datasets.map((dataset, index) => ({
        ...dataset,
        borderColor: chartPalette[index % chartPalette.length],
        backgroundColor: chartPalette[index % chartPalette.length],
        pointRadius: 0,
        pointHoverRadius: 4,
        borderWidth: 2,
        tension: 0.3,
        spanGaps: true,
        fill: false
    }));
}

// Batasi rentang tanggal maksimal maxRangeDays hari (inklusif)
(function () {
    const fromInput = document.getElementById('filterFrom');
    const toInput = document.getElementById('filterTo');
    const today = toInput.max;

    function shiftDate(value, days) {
        const date = new Date(value + 'T00:00:00');
        date.setDate(date.getDate() + days);
        const pad = (n) => String(n).padStart(2, '0');
        return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
    }

    function minDate(a, b) {
        return a < b ? a : b;
    }

    function syncLimits() {
        // "Dari" bebas dipilih; "Sampai" dibatasi maksimal maxRangeDays hari setelahnya
        if (fromInput.value) {
            toInput.min = fromInput.value;
            toInput.max = minDate(shiftDate(fromInput.value, maxRangeDays - 1), today);
        }
    }

    fromInput.addEventListener('change', function () {
        if (!fromInput.value) {
            return;
        }
        const latestTo = minDate(shiftDate(fromInput.value, maxRangeDays - 1), today);
        if (!toInput.value || toInput.value < fromInput.value || toInput.value > latestTo) {
            toInput.value = latestTo < fromInput.value ? fromInput.value : latestTo;
        }
        syncLimits();
    });

    toInput.addEventListener('change', function () {
        if (!toInput.value) {
            return;
        }
        const earliestFrom = shiftDate(toInput.value, -(maxRangeDays - 1));
        if (!fromInput.value || fromInput.value > toInput.value || fromInput.value < earliestFrom) {
            fromInput.value = earliestFrom;
        }
        syncLimits();
    });

    syncLimits();
})();

if (document.getElementById('lineChart')) {
    new Chart(document.getElementById('lineChart'), {
        type: 'line',
        data: {
            labels: chartLabels,
            datasets: stylizeDatasets(chartDatasets)
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        pointStyle: 'rectRounded',
                        boxWidth: 10,
                        boxHeight: 8,
                        padding: 10,
                        font: { size: 10 }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = context.parsed.y ?? '-';
                            return `${context.dataset.label}: ${value}${chartUnit ? ' ' + chartUnit : ''}`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    type: 'category',
                    ticks: {
                        maxRotation: 0,
                        autoSkip: true,
                        maxTicksLimit: 10
                    }
                },
                y: {
                    beginAtZero: false
                }
            }
        }
    });
}
</script>
@endsection
