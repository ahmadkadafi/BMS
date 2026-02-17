@extends('layouts.master')

@section('title', 'Table - '.$resor->nama)

@section('content')
<style>
    .table-filter-card {
        border: 0;
        border-radius: 16px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
    }
    .table-data-card {
        border: 0;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 10px 26px rgba(0, 0, 0, 0.08);
    }
    .table thead th {
        white-space: nowrap;
        font-size: 12px;
    }
    .table tbody td {
        font-size: 12px;
        white-space: nowrap;
    }
</style>

<div class="page-inner">

<div class="card table-filter-card mb-4">
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
                    <input type="date" name="from" value="{{ request('from') }}" class="form-control">
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold"><i class="fas fa-calendar-check text-primary mr-1"></i> Sampai Tanggal</label>
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

<div class="d-flex justify-content-center">
    @if ($times->hasPages())
    <div class="d-flex justify-content-center my-3">
        <ul class="pagination pagination-sm">
            <li class="page-item {{ $times->onFirstPage() ? 'disabled' : '' }}">
                <a class="page-link" href="{{ $times->url(1) }}">First</a>
            </li>
            <li class="page-item {{ $times->onFirstPage() ? 'disabled' : '' }}">
                <a class="page-link" href="{{ $times->previousPageUrl() }}">Prev</a>
            </li>

            @foreach ($times->getUrlRange(max(1, $times->currentPage() - 2), min($times->lastPage(), $times->currentPage() + 2)) as $page => $url)
                <li class="page-item {{ $page == $times->currentPage() ? 'active' : '' }}">
                    <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                </li>
            @endforeach

            <li class="page-item {{ $times->hasMorePages() ? '' : 'disabled' }}">
                <a class="page-link" href="{{ $times->nextPageUrl() }}">Next</a>
            </li>
            <li class="page-item {{ $times->hasMorePages() ? '' : 'disabled' }}">
                <a class="page-link" href="{{ $times->url($times->lastPage()) }}">Last</a>
            </li>
        </ul>
    </div>
    @endif
</div>

@php
    $cellCards = [
        'volt' => ['title' => ' Voltage Cell (V)', 'icon' => 'fas fa-bolt text-warning'],
        'temp' => ['title' => ' Temperature Cell (°C)', 'icon' => 'fas fa-thermometer-half text-danger'],
        'thd' => ['title' => ' THD Cell', 'icon' => 'fas fa-wave-square text-info'],
        'soc' => ['title' => ' SOC Cell (%)', 'icon' => 'fas fa-chart-line text-primary'],
        'soh' => ['title' => ' SOH Cell (%)', 'icon' => 'fas fa-heartbeat text-success'],
    ];
@endphp

@foreach ($cellCards as $metric => $meta)
<div class="card table-data-card mb-4">
    <div class="card-header fw-bold d-flex justify-content-between align-items-center">
        <span><i class="{{ $meta['icon'] }} mr-1"></i>{{ $meta['title'] }}</span>
        <small class="text-muted">{{ $times->total() }} snapshot</small>
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
                        @for ($i = 1; $i <= $maxBatt; $i++)
                            <th>Batt {{ $i }}</th>
                        @endfor
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $row)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ \Carbon\Carbon::parse($row['time'])->format('d/m/Y') }}</td>
                        <td>{{ \Carbon\Carbon::parse($row['time'])->format('H:i:s') }}</td>
                        <td>{{ $row['gardu'] }}</td>
                        @for ($i = 0; $i < $maxBatt; $i++)
                            @php $batt = $row['batteries'][$i] ?? null; @endphp
                            <td>{{ $batt?->{$metric} ?? '-' }}</td>
                        @endfor
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ 4 + $maxBatt }}" class="text-center text-muted py-3">
                            Tidak ada data untuk filter yang dipilih.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endforeach

<div class="card table-data-card mb-4">
    <div class="card-header fw-bold d-flex justify-content-between align-items-center">
        <span><i class="fas fa-charging-station text-secondary mr-1"></i> Voltage Charge dan Current Charge</span>
        <small class="text-muted">{{ $times->total() }} snapshot</small>
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
                        @for ($i = 1; $i <= $maxCharger; $i++)
                            <th>Charger {{ $i }} Voltage</th>
                            <th>Charger {{ $i }} Current</th>
                        @endfor
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $row)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ \Carbon\Carbon::parse($row['time'])->format('d/m/Y') }}</td>
                        <td>{{ \Carbon\Carbon::parse($row['time'])->format('H:i:s') }}</td>
                        <td>{{ $row['gardu'] }}</td>
                        @for ($i = 0; $i < $maxCharger; $i++)
                            @php $charger = $row['chargers'][$i] ?? null; @endphp
                            <td>{{ $charger?->voltage ?? '-' }}</td>
                            <td>{{ $charger?->current ?? '-' }}</td>
                        @endfor
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ 4 + ($maxCharger * 2) }}" class="text-center text-muted py-3">
                            Tidak ada data untuk filter yang dipilih.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

</div>
@endsection

@section('adding')
<style>
.pagination { gap: 4px; }
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
