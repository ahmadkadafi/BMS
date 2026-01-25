@extends('layouts.master')

@section('title', 'Table - '.$resor->nama)

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
                    <input type="date" name="from" value="{{ request('from') }}" class="form-control">
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold">Sampai Tanggal</label>
                    <input type="date" name="to" value="{{ request('to') }}" class="form-control">
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
$metrics = [
    'volt' => 'Voltage (V)',
    'temp' => 'Temperature (°C)',
    'thd'  => 'THD (%)',
    'soc'  => 'SOC (%)',
    'soh'  => 'SOH (%)',
];
@endphp

{{-- ================= PAGINATION ================= --}}
<div class="d-flex justify-content-center">
    @if ($times->hasPages())
    <div class="d-flex justify-content-center my-4">
        <ul class="pagination pagination-sm">

            {{-- FIRST --}}
            <li class="page-item {{ $times->onFirstPage() ? 'disabled' : '' }}">
                <a class="page-link" href="{{ $times->url(1) }}">⏮ First</a>
            </li>

            {{-- PREV --}}
            <li class="page-item {{ $times->onFirstPage() ? 'disabled' : '' }}">
                <a class="page-link" href="{{ $times->previousPageUrl() }}">◀ Prev</a>
            </li>

            {{-- PAGE NUMBERS --}}
            @foreach ($times->getUrlRange(
                max(1, $times->currentPage() - 2),
                min($times->lastPage(), $times->currentPage() + 2)
            ) as $page => $url)
                <li class="page-item {{ $page == $times->currentPage() ? 'active' : '' }}">
                    <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                </li>
            @endforeach

            {{-- NEXT --}}
            <li class="page-item {{ $times->hasMorePages() ? '' : 'disabled' }}">
                <a class="page-link" href="{{ $times->nextPageUrl() }}">Next ▶</a>
            </li>

            {{-- LAST --}}
            <li class="page-item {{ $times->hasMorePages() ? '' : 'disabled' }}">
                <a class="page-link" href="{{ $times->url($times->lastPage()) }}">Last ⏭</a>
            </li>

        </ul>
    </div>
    @endif

</div>

{{-- ================= MULTI TABLE ================= --}}
@foreach ($metrics as $key => $title)
<div class="card mb-4">
    <div class="card-header fw-bold">
        {{ $title }}
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-sm text-center align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>No</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Lokasi</th>

                        @php
                            $maxBatt = collect($data)
                                ->flatMap(fn($d) => $d['rows'])
                                ->max('battery_id');
                        @endphp

                        @for ($i = 1; $i <= count($data[0]['rows'] ?? []); $i++)
                            <th>Batt {{ $i }}</th>
                        @endfor
                    </tr>
                </thead>

                <tbody>
                    @foreach ($data as $i => $d)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ \Carbon\Carbon::parse($d['time'])->format('d/m/Y') }}</td>
                        <td>{{ \Carbon\Carbon::parse($d['time'])->format('H:i:s') }}</td>
                        <td>{{ $d['gardu'] }}</td>

                        @foreach ($d['rows'] as $row)
                            <td>{{ $row->$key }}</td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endforeach

</div>
@endsection

@section('adding')
<style>
.pagination {
    gap: 4px;
}

.page-item .page-link {
    border-radius: 6px;
    padding: 6px 10px;
    font-size: 13px;
    color: #0d6efd;
}

.page-item.active .page-link {
    background-color: #0d6efd;
    border-color: #0d6efd;
    color: #fff;
}

.page-item.disabled .page-link {
    color: #adb5bd;
    pointer-events: none;
    background-color: #f8f9fa;
}
</style>
@endsection
