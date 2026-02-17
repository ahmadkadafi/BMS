@extends('layouts.master')

@section('title', 'Charger Setting - '.$resor->nama)

@section('content')
<div class="page-inner">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-header">
            <div class="d-flex align-items-center gap-2">
                <i class="fas fa-charging-station"></i>
                <h6 class="fw-bold mb-0">Charger Setting</h6>
            </div>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered align-middle text-center">
                <thead class="table-dark align-middle">
                    <tr>
                        <th rowspan="2">No</th>
                        <th rowspan="2">Gardu</th>
                        <th rowspan="2">Charger</th>
                        <th rowspan="2">Float (V)</th>
                        <th rowspan="2">Boost (V)</th>
                        <th colspan="3">Warning</th>
                        <th colspan="3">Alarm</th>
                        <th rowspan="2">Action</th>
                    </tr>
                    <tr>
                        <th>Min Volt (V)</th>
                        <th>Max Volt (V)</th>
                        <th>Curr (A)</th>
                        <th>Min Volt (V)</th>
                        <th>Max Volt (V)</th>
                        <th>Curr (A)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($chargers as $charger)
                        @php
                            $s = $charger->chargerSetting;
                        @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="fw-semibold">{{ $charger->gardu?->nama ?? '-' }}</td>
                            <td class="text-center">
                                <div class="fw-semibold">{{ $charger->merk }}</div>
                            </td>
                            <td>{{ $s->float ?? '-' }}</td>
                            <td>{{ $s->boost ?? '-' }}</td>
                            <td>{{ $s->warn_min_volt ?? '-' }}</td>
                            <td>{{ $s->warn_max_volt ?? '-' }}</td>
                            <td>{{ $s->warn_curr ?? '-' }}</td>
                            <td>{{ $s->alarm_min_volt ?? '-' }}</td>
                            <td>{{ $s->alarm_max_volt ?? '-' }}</td>
                            <td>{{ $s->alarm_curr ?? '-' }}</td>
                            <td>
                                <div class="d-flex justify-content-center gap-2">
                                    <button class="btn btn-warning btn-icon btn-sm"
                                            data-bs-toggle="modal"
                                            data-bs-target="#edit{{ $charger->id }}"
                                            title="Edit Setting">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    @if ($s)
                                        <button class="btn btn-danger btn-icon btn-sm"
                                                data-bs-toggle="modal"
                                                data-bs-target="#delete{{ $charger->id }}"
                                                title="Hapus Setting">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @include('page.chargersetting_modal')
                    @empty
                        <tr>
                            <td colspan="12" class="text-muted py-4">Data charger belum tersedia untuk resor ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('adding')
<style>
.btn-icon {
    width: 36px;
    height: 36px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}
.table th, .table td {
    white-space: nowrap;
    font-size: 13px;
}
</style>
@endsection
