@extends('layouts.master')

@section('title', 'Monitoring - '.$resor->nama)

@section('content')
<div class="page-inner">

    {{-- FILTER GARDU --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET">
                <div class="row align-items-center">
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

    {{-- LOOP GARDU --}}
    @forelse ($filteredGardu as $g)
        @php
            $latest = $g->batteries
                ->flatMap->monitorings
                ->sortByDesc('measured_at')
                ->first();
        @endphp

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    {{ $g->nama }}
                    <small class="text-muted">
                        ({{ $g->n_batt }} Battery)
                    </small>
                </h5>
            </div>

            <div class="card-body">
                <div class="row">
                    @foreach ($g->batteries as $b)
                        @php
                            $last = $b->monitorings
                                ->sortByDesc('measured_at')
                                ->first();
                        @endphp

                        <div class="col-xl-2 col-lg-3 col-md-6 mb-3">
                            <div class="card card-stats card-round h-100 card-center">
                                <div class="card-header text-center">
                                    <strong>Battery {{ $loop->iteration }}</strong>
                                </div>

                                <div class="card-body text-center">
                                    <div class="d-flex justify-content-between">
                                        <h6 class="mb-1">Temp</h6>
                                        <h6>{{ $last->temp ?? '-' }} °C</h6>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <h6 class="mb-1">Volt</h6>
                                        <h6>{{ $last->volt ?? '-' }} V</h6>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <h6 class="mb-1">SOC</h6>
                                        <h6>{{ $last->soc ?? '-' }} %</h6>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <h6 class="mb-1">SOH</h6>
                                        <h6>{{ $last->soh ?? '-' }} %</h6> 
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <h6 class="mb-1">THD</h6>
                                        <h6>{{ $last->thd ?? '-' }} m&#8486</h6> 
                                    </div>                                                                    
                                </div>                             
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="card-footer text-center">
                    <p class="text-muted mt-2 mb-0" style="font-size: 12px">
                        upadated {{ $last?->measured_at?->format('d/m/Y H:i') ?? '-' }}
                    </p>
            </div>
        </div>

    @empty
        <div class="alert alert-warning">
            Tidak ada data gardu pada resor ini.
        </div>
    @endforelse

</div>
@endsection