@extends('layouts.master')

@section('title', 'Logger - '.$resor->nama)

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
                            <option value="warning" {{ request('status') === 'warning' ? 'selected' : '' }}>Warning</option>
                            <option value="alarm" {{ request('status') === 'alarm' ? 'selected' : '' }}>Alarm</option>
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

                    <div class="col-md-6 col-lg-3">
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
                            <th>occurred_at</th>
                            <th>device_type</th>
                            <th>daop_id</th>
                            <th>resor_id</th>
                            <th>gardu_id</th>
                            <th>batt_id</th>
                            <th>charger_id</th>
                            <th>status</th>
                            <th>action</th>
                            <th>message</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr>
                                <td class="text-center">{{ $logs->firstItem() + $loop->index }}</td>
                                <td class="text-nowrap">{{ $log->occurred_at?->format('d/m/Y H:i:s') }}</td>
                                <td class="text-center">{{ $log->device_type }}</td>
                                <td class="text-center">{{ $log->daop_id ?? '-' }}</td>
                                <td class="text-center">
                                    {{ $log->resor_id }}
                                    <div class="small text-muted">{{ $log->resor?->nama }}</div>
                                </td>
                                <td class="text-nowrap">
                                    {{ $log->gardu_id }}
                                    <div class="small text-muted">{{ $log->gardu?->nama }}</div>
                                </td>
                                <td class="text-center">{{ $log->batt_id ?? '-' }}</td>
                                <td class="text-center">{{ $log->charger_id ?? '-' }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $log->status === 'alarm' ? 'bg-danger' : 'bg-warning text-dark' }}">
                                        {{ strtoupper($log->status) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if ($log->action === 'coming')
                                        <form action="{{ route('logger.submit', ['resor' => $resor->id, 'logger' => $log->id]) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm {{ $log->status === 'alarm' ? 'btn-danger' : 'btn-warning text-dark' }}">
                                                Submit {{ ucfirst($log->status) }}
                                            </button>
                                        </form>
                                    @else
                                        <span class="badge bg-success">{{ ucfirst($log->action) }}</span>
                                    @endif
                                </td>
                                <td>{{ $log->message }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center text-muted py-4">
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
