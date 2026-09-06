@extends('layouts.master')

@section('title', 'Logger - '.($resor?->nama ?? 'Semua Data'))

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
    .logger-table .btn-sm {
        font-size: 11px;
        padding: 3px 7px;
    }
    .logger-table .logger-message {
        min-width: 220px;
        max-width: 360px;
        white-space: normal;
    }
</style>

<div class="page-inner">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card table-filter-card mb-4">
        <div class="card-body">
            <form method="GET">
                <div class="row g-3 align-items-end">
                    <div class="col-md-6 col-lg-3">
                        <label class="form-label fw-bold">Lokasi Gardu</label>
                        <select name="gardu_id" class="form-select">
                            <option value="">-- Semua Gardu --</option>
                            @foreach ($garduOptions as $gardu)
                                <option value="{{ $gardu->id }}" {{ (string) request('gardu_id') === (string) $gardu->id ? 'selected' : '' }}>
                                    {{ $gardu->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 col-lg-2">
                        <label class="form-label fw-bold">Status</label>
                        <select name="status" class="form-select">
                            <option value="">-- Semua Status --</option>
                            <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Open</option>
                            <option value="confirm" {{ request('status') === 'confirm' ? 'selected' : '' }}>Confirm</option>
                            <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Closed</option>
                        </select>
                    </div>

                    <div class="col-md-6 col-lg-2">
                        <label class="form-label fw-bold">Alarm Type</label>
                        <select name="alarm_type" class="form-select">
                            <option value="">-- Semua Type --</option>
                            <option value="warning" {{ request('alarm_type') === 'warning' ? 'selected' : '' }}>Warning</option>
                            <option value="alarm" {{ request('alarm_type') === 'alarm' ? 'selected' : '' }}>Alarm</option>
                        </select>
                    </div>

                    <div class="col-md-6 col-lg-2">
                        <label class="form-label fw-bold">Dari Tanggal</label>
                        <input type="date" name="from" value="{{ request('from') }}" class="form-control">
                    </div>

                    <div class="col-md-6 col-lg-2">
                        <label class="form-label fw-bold">Sampai Tanggal</label>
                        <input type="date" name="to" value="{{ request('to') }}" class="form-control">
                    </div>

                    <div class="col-md-6 col-lg-1">
                        <button class="btn btn-primary w-100">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card table-data-card mb-4">
        <div class="card-header fw-bold d-flex justify-content-between align-items-center">
            <span><i class="fas fa-bell text-danger mr-1"></i> Logger Warning & Alarm</span>
            <small class="text-muted">{{ $logs->total() }} data</small>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm text-center align-middle logger-table">
                    <thead class="table-dark">
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>Waktu</th>
                            <th>Status</th>
                            <th>Device Type</th>
                            <th>Gardu</th>
                            <th>Device Name</th>
                            <th>Duration</th>
                            <th>Alarm Type</th>
                            <th>Message</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            @php
                                $lifecycleStatus = $log->lifecycle_status ?? match ($log->action) {
                                    'handled' => 'confirm',
                                    'released' => 'closed',
                                    default => 'open',
                                };
                                $alarmType = $log->alarm_type ?? $log->status;
                                $statusBadge = match ($lifecycleStatus) {
                                    'closed' => 'bg-success',
                                    'confirm' => 'bg-info text-dark',
                                    default => 'bg-secondary',
                                };
                                $alarmBadge = $alarmType === 'alarm' ? 'bg-danger' : 'bg-warning text-dark';
                            @endphp
                            <tr>
                                <td class="text-center">{{ $logs->firstItem() + $loop->index }}</td>
                                <td class="text-nowrap">{{ $log->occurred_at?->format('d/m/Y') }}</td>
                                <td class="text-nowrap">{{ $log->occurred_at?->format('H:i:s') }}</td>
                                <td class="text-nowrap">
                                    <span class="badge {{ $statusBadge }}">{{ strtoupper($lifecycleStatus) }}</span>
                                </td>
                                <td class="text-center">{{ ucfirst($log->device_type) }}</td>
                                <td class="text-center">
                                    {{ $log->gardu?->nama ?? '-' }}
                                </td>
                                <td class="text-center">
                                    @if ($log->device_type === 'battery')
                                        {{ $log->battery?->serial_no ?? $log->batt_id ?? '-' }}
                                    @elseif ($log->device_type === 'charger')
                                        {{ $log->charger?->merk ?? $log->charger?->serial_no ?? $log->charger_id ?? '-' }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-center">{{ $log->duration_text ?? '-' }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $alarmBadge }}">
                                        {{ strtoupper($alarmType) }}
                                    </span>
                                </td>
                                <td class="logger-message">{{ $log->message }}</td>
                                <td class="text-center">
                                    @if ($lifecycleStatus === 'open')
                                        <div class="d-flex justify-content-center gap-1">
                                            <form action="{{ route('logger.submit', ['resor' => $resor?->id ?? $log->resor_id, 'logger' => $log->id]) }}" method="POST">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="action" value="confirm">
                                                <button type="submit" class="btn btn-sm btn-info text-dark" title="Confirm">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                            <form action="{{ route('logger.submit', ['resor' => $resor?->id ?? $log->resor_id, 'logger' => $log->id]) }}" method="POST">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="action" value="close">
                                                <button type="submit" class="btn btn-sm btn-success" title="Close">
                                                    <i class="fas fa-lock"></i>
                                                </button>
                                            </form>
                                        </div>
                                    @elseif ($lifecycleStatus === 'confirm')
                                        <form action="{{ route('logger.submit', ['resor' => $resor?->id ?? $log->resor_id, 'logger' => $log->id]) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="action" value="close">
                                            <button type="submit" class="btn btn-sm btn-success" title="Close">
                                                <i class="fas fa-lock"></i>
                                            </button>
                                        </form>
                                    @else
                                        <span class="badge bg-success" title="Closed">
                                            <i class="fas fa-check-circle"></i>
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">
                                    Tidak ada data warning/alarm berdasarkan battery setting.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $logs->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
