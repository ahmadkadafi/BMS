@extends('layouts.master')

@section('title', 'Graphic - '.$resor->nama)

@section('content')
<div class="page-inner">

    {{-- FILTER GARDU --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET">
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Pilih Lokasi Gardu</label>
                        <select name="gardu_id" class="form-select" onchange="this.form.submit()">
                            <option value="">-- Semua Gardu --</option>
                            @foreach ($gardu as $g)
                                <option value="{{ $g->id }}"
                                    {{ request('gardu_id') == $g->id ? 'selected' : '' }}>
                                    {{ $g->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>
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
        @endphp

        <div class="card mb-5">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    {{ $g->nama }}
                    <small class="text-muted">({{ count($labels) }} Battery)</small>
                </h5>
            </div>

            <div class="card-body">
                <div class="row g-4">

                    {{-- VOLT --}}
                    <div class="col-lg-6">
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
                    <div class="col-lg-6">
                        <div class="chart-card">
                            <div class="chart-header">
                                <i class="fas fa-thermometer-half text-danger"></i>
                                Temperature (°C)
                            </div>
                            <div class="chart-box">
                                <canvas id="tempChart{{ $g->id }}"></canvas>
                            </div>
                        </div>
                    </div>

                    {{-- THD --}}
                    <div class="col-lg-6">
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
                    <div class="col-lg-6">
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
                    <div class="col-lg-12">
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
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 15px;
    background: #fff;
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
