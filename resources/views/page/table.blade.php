@extends('layouts.master')

@section('title', 'Table - '.($resor?->nama ?? 'Semua Data'))

@section('content')
@php
    $cellCards = [
        'volt' => ['title' => ' Voltage Cell (V)', 'icon' => 'fas fa-bolt text-warning'],
        'temp' => ['title' => ' Temperature Cell (°C)', 'icon' => 'fas fa-thermometer-half text-danger'],
        'thd' => ['title' => ' IR Cell (m&#8486;)', 'icon' => 'fas fa-wave-square text-info'],
        'soc' => ['title' => ' SOC Cell (%)', 'icon' => 'fas fa-chart-line text-primary'],
        'soh' => ['title' => ' SOH Cell (%)', 'icon' => 'fas fa-heartbeat text-success'],
        'charger' => ['title' => ' Charger', 'icon' => 'fas fa-charging-station text-secondary'],
    ];
    $meta = $cellCards[$selectedTable] ?? $cellCards['volt'];
@endphp

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
                <div class="col-md-2">
                    <label class="form-label fw-bold"><i class="fas fa-map-marker-alt text-primary mr-1"></i> Lokasi Gardu</label>
                    <select name="gardu_id" class="form-select">
                        <option value="" {{ empty($selectedGarduId) ? 'selected' : '' }}>-- Semua Gardu --</option>
                        @foreach ($gardu as $g)
                            <option value="{{ $g->id }}" {{ (int) $selectedGarduId === (int) $g->id ? 'selected' : '' }}>
                                {{ $g->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-bold"><i class="fas fa-table text-primary mr-1"></i> Table</label>
                    <select name="table" class="form-select">
                        <option value="volt" {{ $selectedTable == 'volt' ? 'selected' : '' }}>Voltage Cell</option>
                        <option value="temp" {{ $selectedTable == 'temp' ? 'selected' : '' }}>Temperature Cell</option>
                        <option value="thd" {{ $selectedTable == 'thd' ? 'selected' : '' }}>IR Cell</option>
                        <option value="soc" {{ $selectedTable == 'soc' ? 'selected' : '' }}>SOC Cell</option>
                        <option value="soh" {{ $selectedTable == 'soh' ? 'selected' : '' }}>SOH Cell</option>
                        <option value="charger" {{ $selectedTable == 'charger' ? 'selected' : '' }}>Charger</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-bold"><i class="fas fa-calendar-alt text-primary mr-1"></i> Dari Tanggal</label>
                    <input type="date" name="from" value="{{ request('from') }}" class="form-control">
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-bold"><i class="fas fa-calendar-check text-primary mr-1"></i> Sampai Tanggal</label>
                    <input type="date" name="to" value="{{ request('to') }}" class="form-control">
                </div>

                <div class="col-md-2">
                    <button class="btn btn-primary w-100">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>

                <div class="col-md-2">
                    <button type="submit" name="export" value="1" class="btn btn-success w-100">
                        <i class="fas fa-download"></i> Export to CSV
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card table-data-card mb-4">
    <div class="card-header fw-bold d-flex justify-content-between align-items-center">
        <span><i class="{{ $meta['icon'] }} mr-1"></i>{{ $meta['title'] }}</span>
        <small class="text-muted">{{ $rows->total() }} data</small>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-sm text-center align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>No</th>
                        <th>Date</th>
                        <th>Time</th>
                        @if ($selectedTable === 'charger')
                            <th>Volt Charge</th>
                            <th>Current Charge</th>
                        @else
                            <th>Lokasi</th>
                            @for ($i = 1; $i <= $maxBatt; $i++)
                                <th>Batt {{ $i }}</th>
                            @endfor
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                    <tr>
                        <td>{{ $rows->firstItem() + $loop->index }}</td>
                        <td>{{ \Carbon\Carbon::parse($row['time'])->format('d/m/Y') }}</td>
                        <td>{{ \Carbon\Carbon::parse($row['time'])->format('H:i:s') }}</td>
                        @if ($selectedTable === 'charger')
                            @php
                                $chargerVoltages = collect($row['chargers'])
                                    ->pluck('voltage')
                                    ->filter(fn ($value) => $value !== null && $value !== '')
                                    ->implode(', ');
                                $chargerCurrents = collect($row['chargers'])
                                    ->pluck('current')
                                    ->filter(fn ($value) => $value !== null && $value !== '')
                                    ->implode(', ');
                            @endphp
                            <td>{{ $chargerVoltages !== '' ? $chargerVoltages : '-' }}</td>
                            <td>{{ $chargerCurrents !== '' ? $chargerCurrents : '-' }}</td>
                        @else
                            <td>{{ $row['gardu'] }}</td>
                            @for ($i = 0; $i < $maxBatt; $i++)
                                @php $batt = $row['batteries'][$i] ?? null; @endphp
                                <td>{{ $batt?->{$selectedTable} ?? '-' }}</td>
                            @endfor
                        @endif
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $selectedTable === 'charger' ? 5 : 4 + $maxBatt }}" class="text-center text-muted py-3">
                            Tidak ada data untuk filter yang dipilih.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($rows->hasPages())
            <div class="d-flex justify-content-center mt-4">
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item {{ $rows->onFirstPage() ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ $rows->url(1) }}">First</a>
                    </li>
                    <li class="page-item {{ $rows->onFirstPage() ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ $rows->previousPageUrl() }}">Prev</a>
                    </li>

                    @foreach ($rows->getUrlRange(max(1, $rows->currentPage() - 2), min($rows->lastPage(), $rows->currentPage() + 2)) as $page => $url)
                        <li class="page-item {{ $page == $rows->currentPage() ? 'active' : '' }}">
                            <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                        </li>
                    @endforeach

                    <li class="page-item {{ $rows->hasMorePages() ? '' : 'disabled' }}">
                        <a class="page-link" href="{{ $rows->nextPageUrl() }}">Next</a>
                    </li>
                    <li class="page-item {{ $rows->hasMorePages() ? '' : 'disabled' }}">
                        <a class="page-link" href="{{ $rows->url($rows->lastPage()) }}">Last</a>
                    </li>
                </ul>
            </div>
        @endif
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
