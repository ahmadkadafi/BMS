@extends('layouts.master')

@section('title', 'Line - '.$resor->nama)

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
        <div class="card mb-5">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    {{ $g->nama }}
                    <small class="text-muted">(Last 7 Days)</small>
                </h5>
            </div>

            <div class="card-body">
                <div class="row g-4">

                    @foreach ([
                        ['key'=>'volt','label'=>'Voltage (V)','color'=>'#28a745','icon'=>'battery-three-quarters'],
                        ['key'=>'temp','label'=>'Temperature (°C)','color'=>'#dc3545','icon'=>'thermometer-half'],
                        ['key'=>'thd','label'=>'THD (%)','color'=>'#17a2b8','icon'=>'wave-square'],
                        ['key'=>'soc','label'=>'SOC (%)','color'=>'#007bff','icon'=>'percent'],
                        ['key'=>'soh','label'=>'SOH (%)','color'=>'#ffc107','icon'=>'heartbeat'],
                    ] as $chart)
                        <div class="col-lg-6">
                            <div class="chart-card">
                                <div class="chart-header">
                                    <i class="fas fa-{{ $chart['icon'] }}"
                                       style="color: {{ $chart['color'] }}"></i>
                                    {{ $chart['label'] }}
                                </div>
                                <div class="chart-box">
                                    <canvas id="{{ $chart['key'] }}Chart{{ $g->id }}"></canvas>
                                </div>
                            </div>
                        </div>
                    @endforeach

                </div>
            </div>
        </div>

        {{-- PREPARE DATA --}}
        <script>
        @foreach ($filteredGardu as $g)

            {{-- ambil timeline dari battery pertama (300 data terakhir) --}}
            const labels{{ $g->id }} = @json(
                optional($g->batteries->first())
                    ?->monitorings
                    ->sortByDesc('measured_at')
                    ->take(300)
                    ->sortBy('measured_at')
                    ->map(fn($m) => \Carbon\Carbon::parse($m->measured_at)->format('d/m H:i'))
                    ->values()
            );

            @foreach (['volt','temp','thd','soc','soh'] as $metric)
                const {{ $metric }}Data{{ $g->id }} = [
                    @foreach ($g->batteries as $b)
                        {
                            label: 'Battery {{ $loop->iteration }}',
                            data: @json(
                                $b->monitorings
                                    ->sortByDesc('measured_at')
                                    ->take(300)
                                    ->sortBy('measured_at')
                                    ->pluck($metric)
                                    ->values()
                            )
                        },
                    @endforeach
                ];
            @endforeach

        @endforeach
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
    height: 260px;
}
</style>

<script>
function lineChart(id, labels, datasets) {
    new Chart(document.getElementById(id), {
        type: 'line',
        data: {
            labels: labels,
            datasets: datasets.map((d, i) => ({
                label: d.label,
                data: d.data,
                borderWidth: 2,
                fill: false,
                tension: 0.35
            }))
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,   
                        pointStyle: 'rect',    
                        boxWidth: 10,          
                        boxHeight: 10,
                        padding: 12,
                        font: {
                            size: 11
                        }
                    }
                }
            },
            scales: {
                x: { ticks: { maxTicksLimit: 10 } },
                y: { beginAtZero: false }
            }
        }
    });
}

@foreach ($filteredGardu as $g)
lineChart('voltChart{{ $g->id }}', labels{{ $g->id }}, voltData{{ $g->id }});
lineChart('tempChart{{ $g->id }}', labels{{ $g->id }}, tempData{{ $g->id }});
lineChart('thdChart{{ $g->id }}',  labels{{ $g->id }}, thdData{{ $g->id }});
lineChart('socChart{{ $g->id }}',  labels{{ $g->id }}, socData{{ $g->id }});
lineChart('sohChart{{ $g->id }}',  labels{{ $g->id }}, sohData{{ $g->id }});
@endforeach
</script>
@endsection
