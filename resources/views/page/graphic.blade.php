@extends('layouts.master')

@section('title', 'Graphic - '.$resor->nama)

@section('content')
<style>
    .graphic-filter-card {
        border: 0;
        border-radius: 16px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
    }
    .filter-inline {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: nowrap;
    }
    .filter-inline .form-label {
        margin-bottom: 0;
        white-space: nowrap;
    }
    .filter-inline .form-select {
        width: 320px;
        min-width: 260px;
    }
    .graphic-gardu-card {
        border: 0;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 10px 26px rgba(0, 0, 0, 0.08);
    }
    .graphic-gardu-head {
        background: linear-gradient(90deg, #f8fbff, #edf6ff);
        border-bottom: 1px solid #deebf8;
    }
    .badge-status {
        border-radius: 30px;
        font-size: 11px;
        padding: 6px 10px;
        white-space: normal;
        text-align: center;
    }
</style>

<div class="page-inner">

    {{-- FILTER GARDU --}}
    <div class="card graphic-filter-card mb-4">
        <div class="card-body">
            <form method="GET">
                <div class="filter-inline">
                    <label class="form-label fw-bold"><i class="fa fa-filter mr-1 text-primary"></i>Pilih Lokasi Gardu</label>
                    <select name="gardu_id" class="form-select" onchange="this.form.submit()">
                        <option value="">-- Semua Gardu --</option>
                        @foreach ($gardu as $g)
                            <option value="{{ $g->id }}" {{ request('gardu_id') == $g->id ? 'selected' : '' }}>
                                {{ $g->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    {{-- FILTER DATA --}}
    @php
        $filteredGardu = request('gardu_id')
            ? $gardu->where('id', request('gardu_id'))
            : $gardu;
    @endphp

    @foreach ($filteredGardu as $g)
        @php
            $labels = [];
            $volt = [];
            $temp = [];
            $thd  = [];
            $soc  = [];
            $soh  = [];

            foreach ($g->batteries as $i => $b) {
                $last = $b->monitorings
                    ->sortByDesc('measured_at')
                    ->first();

                $labels[] = 'Battery '.($i+1);
                $volt[] = $last->volt ?? 0;
                $temp[] = $last->temp ?? 0;
                $thd[]  = $last->thd ?? 0;
                $soc[]  = $last->soc ?? 0;
                $soh[]  = $last->soh ?? 0;
            }

            $latestChargerMonitoring = $g->chargers
                ->flatMap->monitorings
                ->sortByDesc('measured_at')
                ->first();
        @endphp

        <div class="card graphic-gardu-card mb-5">
            <div class="card-header graphic-gardu-head">
                <div class="d-flex flex-wrap align-items-center justify-content-between">
                    <h5 class="card-title mb-0">
                        <i class="fa fa-chart-bar text-primary mr-1"></i>{{ $g->nama }}
                        <small class="text-muted">({{ count($labels) }} Battery)</small>
                    </h5>
                    <div class="d-flex flex-wrap justify-content-end" style="gap: 6px;">
                        <span class="badge badge-light text-dark badge-status">
                            <i class="fa fa-charging-station mr-1 text-warning"></i>Volt Charge {{ $latestChargerMonitoring->voltage ?? '-' }} V
                        </span>
                        <span class="badge badge-light text-dark badge-status">
                            <i class="fa fa-tachometer-alt mr-1 text-secondary"></i>Current Charge {{ $latestChargerMonitoring->current ?? '-' }} A
                        </span>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="row g-4">

                    {{-- VOLT --}}
                    <div class="col-lg-4">
                        <div class="chart-card">
                            <div class="chart-header">
                                <i class="fas fa-battery-three-quarters text-success"></i>
                                Voltage Battery (V)
                            </div>
                            <div class="chart-box">
                                <canvas id="voltChart{{ $g->id }}"></canvas>
                            </div>
                        </div>
                    </div>

                    {{-- TEMP --}}
                    <div class="col-lg-4">
                        <div class="chart-card">
                            <div class="chart-header">
                                <i class="fas fa-thermometer-half text-danger"></i>
                                Temperature (&deg;C)
                            </div>
                            <div class="chart-box">
                                <canvas id="tempChart{{ $g->id }}"></canvas>
                            </div>
                        </div>
                    </div>

                    {{-- THD --}}
                    <div class="col-lg-4">
                        <div class="chart-card">
                            <div class="chart-header">
                                <i class="fas fa-wave-square text-info"></i>
                                THD (%)
                            </div>
                            <div class="chart-box">
                                <canvas id="thdChart{{ $g->id }}"></canvas>
                            </div>
                        </div>
                    </div>

                    {{-- SOC --}}
                    <div class="col-lg-4">
                        <div class="chart-card">
                            <div class="chart-header">
                                <i class="fas fa-percent text-primary"></i>
                                SOC (%)
                            </div>
                            <div class="chart-box">
                                <canvas id="socChart{{ $g->id }}"></canvas>
                            </div>
                        </div>
                    </div>

                    {{-- SOH --}}
                    <div class="col-lg-4">
                        <div class="chart-card">
                            <div class="chart-header">
                                <i class="fas fa-heartbeat text-warning"></i>
                                SOH (%)
                            </div>
                            <div class="chart-box">
                                <canvas id="sohChart{{ $g->id }}"></canvas>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- DATA KE JS --}}
        <script>
            const labels{{ $g->id }} = @json($labels);
            const volt{{ $g->id }} = @json($volt);
            const temp{{ $g->id }} = @json($temp);
            const thd{{ $g->id }}  = @json($thd);
            const soc{{ $g->id }}  = @json($soc);
            const soh{{ $g->id }}  = @json($soh);
        </script>
    @endforeach

</div>
@endsection

@section('adding')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
.chart-card {
    border: 1px solid #dfe8f3;
    border-radius: 12px;
    padding: 14px;
    background: linear-gradient(180deg, #ffffff, #f9fcff);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
}

.chart-header {
    font-weight: 600;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.chart-box {
    height: 240px;
}
</style>

<script>
function barChart(id, labels, data, color) {
    new Chart(document.getElementById(id), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: color,
                borderRadius: 6,
                maxBarThickness: 40
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: {
                    ticks: { font: { size: 11 } }
                },
                y: {
                    beginAtZero: true,
                    ticks: { font: { size: 11 } }
                }
            }
        }
    });
}

@foreach ($filteredGardu as $g)
barChart('voltChart{{ $g->id }}', labels{{ $g->id }}, volt{{ $g->id }}, '#28a745');
barChart('tempChart{{ $g->id }}', labels{{ $g->id }}, temp{{ $g->id }}, '#dc3545');
barChart('thdChart{{ $g->id }}',  labels{{ $g->id }}, thd{{ $g->id }},  '#17a2b8');
barChart('socChart{{ $g->id }}',  labels{{ $g->id }}, soc{{ $g->id }},  '#007bff');
barChart('sohChart{{ $g->id }}',  labels{{ $g->id }}, soh{{ $g->id }},  '#ffc107');
@endforeach
</script>
@endsection
