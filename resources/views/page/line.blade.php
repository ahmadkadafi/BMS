@extends('layouts.master')

@section('title', 'Line - '.$resor->nama)

@section('content')
<div class="page-inner">

{{-- ================= FILTER ================= --}}
<div class="card mb-4">
    <div class="card-body">
        <form method="GET">
            <div class="row g-3 align-items-end">

                <div class="col-md-3">
                    <label class="form-label fw-bold">Lokasi Gardu</label>
                    <select name="gardu_id" class="form-select">
                        <option value="">-- Semua Gardu --</option>
                        @foreach ($gardu as $g)
                            <option value="{{ $g->id }}"
                                {{ request('gardu_id') == $g->id ? 'selected' : '' }}>
                                {{ $g->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold">Dari Tanggal</label>
                    <input type="date" name="from" class="form-control"
                           value="{{ request('from') }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold">Sampai Tanggal</label>
                    <input type="date" name="to" class="form-control"
                           value="{{ request('to') }}">
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
@endphp

<div class="card mb-5">
    <div class="card-header">
        <strong>{{ $g->nama }}</strong>
    </div>
    <div class="card-body">
        <div class="row g-4">
            @foreach (['volt'=>'Voltage (V)','temp'=>'Temperature (°C)','thd'=>'THD (%)','soc'=>'SOC (%)','soh'=>'SOH (%)'] as $k=>$title)
            <div class="col-lg-6">
                <div class="chart-card">
                    <div class="chart-header">{{ $title }}</div>
                    <div class="chart-box">
                        <canvas id="{{ $k }}Chart{{ $g->id }}"></canvas>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

<script>
const labels{{ $g->id }} = @json($labels);
const datasets{{ $g->id }} = @json($datasets);
</script>

@endforeach

</div>
@endsection

@section('adding')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
.chart-card{border:1px solid #e9ecef;border-radius:10px;padding:15px}
.chart-box{height:260px}
</style>

<script>
function draw(id, labels, datasets){
    new Chart(document.getElementById(id),{
        type:'line',
        data:{labels:labels,datasets:datasets},
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
                        font: {
                            size: 10
                        }
                    }
                }
            },
            scales:{y:{beginAtZero:false}}
        }
    });
}

@foreach ($filteredGardu as $g)
['volt','temp','thd','soc','soh'].forEach(k=>{
    draw(k+'Chart{{ $g->id }}',labels{{ $g->id }},datasets{{ $g->id }}[k]);
});
@endforeach
</script>
@endsection
