@extends('layouts.master')

@section('title', 'Logger - '.($resor?->nama ?? 'Semua Data'))

@section('content')
<div class="page-inner">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card mb-4">
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

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold">Logger Warning & Alarm</h5>
            <span class="badge bg-secondary">Total: {{ $logs->total() }}</span>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle table-sm">
                    <thead class="table-dark text-center align-middle">
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>Waktu Alarm</th>
                            <th>Status</th>
                            <th>Device Type</th>
                            <th>Device</th>
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
                                    <div class="fw-semibold">{{ $log->gardu?->nama ?? '-' }}</div>
                                    @if ($log->device_type === 'battery')
                                        <div class="small text-muted">Battery: {{ $log->battery?->serial_no ?? $log->batt_id ?? '-' }}</div>
                                    @elseif ($log->device_type === 'charger')
                                        <div class="small text-muted">Charger: {{ $log->charger?->merk ?? $log->charger?->serial_no ?? $log->charger_id ?? '-' }}</div>
                                    @endif
                                </td>
                                <td class="text-center">{{ $log->duration_text ?? '-' }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $alarmBadge }}">
                                        {{ strtoupper($alarmType) }}
                                    </span>
                                    @if ($log->alarm_code)
                                        <div class="small text-muted">{{ ucwords(str_replace('_', ' ', $log->alarm_code)) }}</div>
                                    @endif
                                </td>
                                <td>{{ $log->message }}</td>
                                <td class="text-center">
                                    @if ($lifecycleStatus === 'open')
                                        <div class="d-flex justify-content-center gap-1">
                                            <form action="{{ route('logger.submit', ['resor' => $resor?->id ?? $log->resor_id, 'logger' => $log->id]) }}" method="POST">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="action" value="confirm">
                                                <button type="submit" class="btn btn-sm btn-info text-dark">Confirm</button>
                                            </form>
                                            <form action="{{ route('logger.submit', ['resor' => $resor?->id ?? $log->resor_id, 'logger' => $log->id]) }}" method="POST">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="action" value="close">
                                                <button type="submit" class="btn btn-sm btn-success">Close</button>
                                            </form>
                                        </div>
                                    @elseif ($lifecycleStatus === 'confirm')
                                        <form action="{{ route('logger.submit', ['resor' => $resor?->id ?? $log->resor_id, 'logger' => $log->id]) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="action" value="close">
                                            <button type="submit" class="btn btn-sm btn-success">Close</button>
                                        </form>
                                    @else
                                        <span class="badge bg-success">Done</span>
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
