@extends('layouts.master')

@section('title', 'Line - '.$resor->nama)

@section('content')
<style>
    .line-filter-card {
        border: 0;
        border-radius: 16px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
    }
    .line-gardu-card {
        border: 0;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 10px 26px rgba(0, 0, 0, 0.08);
    }
    .line-gardu-head {
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

{{-- ================= FILTER ================= --}}
<div class="card line-filter-card mb-4">
    <div class="card-body">
        <form method="GET">
            <div class="row g-3 align-items-end">

                <div class="col-md-3">
                    <label class="form-label fw-bold"><i class="fas fa-map-marker-alt text-primary mr-1"></i> Lokasi Gardu</label>
                    <select name="gardu_id" class="form-select">
                        <option value="">-- Semua Gardu --</option>
                        @foreach ($gardu as $g)
                            <option value="{{ $g->id }}" {{ request('gardu_id') == $g->id ? 'selected' : '' }}>
                                {{ $g->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold"><i class="fas fa-calendar-alt text-primary mr-1"></i> Dari Tanggal</label>
                    <input type="date" name="from" class="form-control" value="{{ request('from') }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold"><i class="fas fa-calendar-check text-primary mr-1"></i> Sampai Tanggal</label>
                    <input type="date" name="to" class="form-control" value="{{ request('to') }}">
                </div>

                <div class="col-md-3">
                    <button class="btn btn-primary w-100">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>

            </div>
        </form>
    </div>
</div>

@php
$from = request('from') ? request('from').' 00:00:00' : null;
$to   = request('to')   ? request('to').' 23:59:59' : null;

$filteredGardu = request('gardu_id')
    ? $gardu->where('id', request('gardu_id'))
    : $gardu;
@endphp

@foreach ($filteredGardu as $g)
@php
    $labels = [];
    $datasets = [
        'volt'=>[], 'temp'=>[], 'thd'=>[], 'soc'=>[], 'soh'=>[]
    ];
    $chargeLabels = [];
    $chargeDatasets = [
        'voltage'=>[], 'current'=>[]
    ];

    $base = optional($g->batteries->first())
        ?->monitorings()
        ->when($from && $to, fn($q)=>$q->whereBetween('measured_at',[$from,$to]))
        ->orderBy('measured_at')
        ->limit(300)
        ->get();

    if ($base) {
        $labels = $base->map(fn($m)=>\Carbon\Carbon::parse($m->measured_at)->format('d/m H:i'));
    }

    foreach ($g->batteries as $i=>$b) {
        foreach (array_keys($datasets) as $metric) {
            $datasets[$metric][] = [
                'label'=>"Battery ".($i+1),
                'data'=>$b->monitorings()
                    ->when($from && $to, fn($q)=>$q->whereBetween('measured_at',[$from,$to]))
                    ->orderBy('measured_at')
                    ->limit(300)
                    ->pluck($metric)
            ];
        }
    }

    $chargeBase = optional($g->chargers->first())
        ?->monitorings()
        ->when($from && $to, fn($q)=>$q->whereBetween('measured_at',[$from,$to]))
        ->orderBy('measured_at')
        ->limit(300)
        ->get();

    if ($chargeBase) {
        $chargeLabels = $chargeBase->map(fn($m)=>\Carbon\Carbon::parse($m->measured_at)->format('d/m H:i'));
    }

    foreach ($g->chargers as $i=>$c) {
        $chargeDatasets['voltage'][] = [
            'label'=>"Charger ".($i+1),
            'data'=>$c->monitorings()
                ->when($from && $to, fn($q)=>$q->whereBetween('measured_at',[$from,$to]))
                ->orderBy('measured_at')
                ->limit(300)
                ->pluck('voltage')
        ];

        $chargeDatasets['current'][] = [
            'label'=>"Charger ".($i+1),
            'data'=>$c->monitorings()
                ->when($from && $to, fn($q)=>$q->whereBetween('measured_at',[$from,$to]))
                ->orderBy('measured_at')
                ->limit(300)
                ->pluck('current')
        ];
    }

@endphp

<div class="card line-gardu-card mb-5">
    <div class="card-header line-gardu-head">
        <div class="d-flex flex-wrap align-items-center justify-content-between">
            <h5 class="card-title mb-0">
                <i class="fas fa-chart-line text-primary mr-1"></i> {{ $g->nama }}
                <small class="text-muted">({{ count($datasets['volt']) }} Battery)</small>
            </h5>            
        </div>
    </div>
    <div class="card-body">
        <div class="row g-4">
            @foreach (['volt'=>'Voltage (V)','temp'=>'Temperature (&deg;C)','thd'=>'THD (%)','soc'=>'SOC (%)','soh'=>'SOH (%)'] as $k=>$title)
            <div class="col-lg-6">
                <div class="chart-card">
                    <div class="chart-header">{!! $title !!}</div>
                    <div class="chart-box">
                        <canvas id="{{ $k }}Chart{{ $g->id }}"></canvas>
                    </div>
                </div>
            </div>
            @endforeach

            <div class="col-lg-6">
                <div class="chart-card">
                    <div class="chart-header">Voltage Charge (V)</div>
                    <div class="chart-box">
                        <canvas id="voltageChargeChart{{ $g->id }}"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="chart-card">
                    <div class="chart-header">Current Charge (A)</div>
                    <div class="chart-box">
                        <canvas id="currentChargeChart{{ $g->id }}"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const labels{{ $g->id }} = @json($labels);
const datasets{{ $g->id }} = @json($datasets);
const chargeLabels{{ $g->id }} = @json($chargeLabels);
const chargeDatasets{{ $g->id }} = @json($chargeDatasets);
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
.chart-box { height: 350px; }
</style>

<script>
const linePalette = [
    '#0d6efd', '#dc3545', '#198754', '#fd7e14', '#6f42c1', '#20c997', '#e83e8c', '#0dcaf0', '#ffc107', '#6c757d'
];

function stylizeDatasets(datasets){
    return datasets.map((ds, idx) => ({
        ...ds,
        borderColor: linePalette[idx % linePalette.length],
        backgroundColor: linePalette[idx % linePalette.length],
        pointRadius: 0,
        pointHoverRadius: 4,
        borderWidth: 2,
        tension: 0.3,
        fill: false
    }));
}

function draw(id, labels, datasets){
    new Chart(document.getElementById(id),{
        type:'line',
        data:{labels:labels,datasets:stylizeDatasets(datasets)},
        options:{
            responsive:true,
            maintainAspectRatio:false,
            plugins:{
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        pointStyle: 'rectRounded',
                        boxWidth: 10,
                        boxHeight: 8,
                        padding: 8,
                        font: { size: 10 }
                    }
                }
            },
            scales:{
                x:{ticks:{maxRotation:0,autoSkip:true,maxTicksLimit:8}},
                y:{beginAtZero:false}
            }
        }
    });
}

@foreach ($filteredGardu as $g)
['volt','temp','thd','soc','soh'].forEach(k=>{
    draw(k+'Chart{{ $g->id }}',labels{{ $g->id }},datasets{{ $g->id }}[k]);
});
draw('voltageChargeChart{{ $g->id }}', chargeLabels{{ $g->id }}, chargeDatasets{{ $g->id }}.voltage);
draw('currentChargeChart{{ $g->id }}', chargeLabels{{ $g->id }}, chargeDatasets{{ $g->id }}.current);
@endforeach
</script>
@endsection
