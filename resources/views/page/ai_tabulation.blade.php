@extends('layouts.master')

@section('title', 'Tabulasi AI Prediction - '.($resor?->nama ?? 'Semua Data'))

@section('content')
@php
    $formUrl = $resor
        ? route('ai-prediction.tabulation.resor', ['resor' => $resor->id])
        : route('ai-prediction.tabulation.all');
    $forecastUrl = $resor
        ? route('ai-prediction.resor', ['resor' => $resor->id, 'gardu_id' => $selectedGardu?->id])
        : route('ai-prediction.all', ['gardu_id' => $selectedGardu?->id]);
    $qualityText = [
        'ok' => ['OK', 'bg-success'],
        'ir_frozen' => ['IR beku', 'bg-secondary'],
        'ood' => ['Di luar data latih', 'bg-secondary'],
    ];
    $fmt = fn ($value, $decimals) => is_null($value) ? '-' : number_format($value, $decimals);
@endphp

<style>
    .ai-card {
        border: 0;
        border-radius: 16px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
    }
    .ai-head {
        background: linear-gradient(90deg, #f8fbff, #edf6ff);
        border-bottom: 1px solid #deebf8;
    }
    .ai-tab-table thead th,
    .ai-tab-table tbody td {
        white-space: nowrap;
        font-size: 12px;
    }
    .pagination { gap: 4px; }
    .page-item .page-link { border-radius: 6px; padding: 6px 10px; font-size: 13px; color: #0d6efd; }
    .page-item.active .page-link { background-color: #0d6efd; border-color: #0d6efd; color: #fff; }
    .page-item.disabled .page-link { color: #adb5bd; pointer-events: none; background-color: #f8f9fa; }
</style>

<div class="page-inner">
    <div class="card ai-card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ $formUrl }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label fw-bold"><i class="fas fa-map-marker-alt text-primary mr-1"></i> Lokasi</label>
                        <select name="gardu_id" class="form-select">
                            @foreach ($gardu as $g)
                                <option value="{{ $g->id }}" {{ $selectedGardu?->id === $g->id ? 'selected' : '' }}>{{ $g->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold"><i class="fas fa-battery-half text-primary mr-1"></i> Sel</label>
                        <select name="cell" class="form-select">
                            <option value="">Semua sel</option>
                            @foreach ($cells as $id => $label)
                                <option value="{{ $id }}" {{ $selectedCell === $id ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label fw-bold">Horizon</label>
                        <select name="horizon" class="form-select">
                            <option value="">Semua</option>
                            @foreach ($horizons as $h)
                                <option value="{{ $h }}" {{ $selectedHorizon === $h ? 'selected' : '' }}>H+{{ $h }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold"><i class="fas fa-calendar-alt text-primary mr-1"></i> Target dari</label>
                        <input type="date" name="from" id="filterFrom" value="{{ $fromDate }}" max="{{ $maxDate }}" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold"><i class="fas fa-calendar-check text-primary mr-1"></i> Target sampai</label>
                        <input type="date" name="to" id="filterTo" value="{{ $toDate }}" max="{{ $maxDate }}" class="form-control" required>
                    </div>
                    <div class="col-md-1">
                        <button class="btn btn-primary w-100"><i class="fas fa-filter"></i> Filter</button>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" name="export" value="1" class="btn btn-success w-100">
                            <i class="fas fa-download"></i> Export CSV
                        </button>
                    </div>
                </div>
                <small class="text-muted d-block mt-2">
                    <i class="fas fa-info-circle mr-1"></i> Tanggal yang difilter adalah tanggal target prediksi, maksimal {{ \App\Http\Controllers\AiPredictionController::TABULATION_MAX_DAYS }} hari.
                    Nilai aktual adalah IR harian sel pada tanggal target. <a href="{{ $forecastUrl }}">Kembali ke forecast</a>.
                </small>
            </form>
        </div>
    </div>

    @if ($rangeNotice)
        <div class="alert alert-warning"><i class="fas fa-exclamation-triangle mr-1"></i> {{ $rangeNotice }}</div>
    @endif

    <div class="card ai-card mb-4">
        <div class="card-header ai-head">
            <span class="fw-bold"><i class="fas fa-bullseye text-primary mr-1"></i> Ringkasan Akurasi</span>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-sm mb-2 text-center">
                <thead>
                    <tr>
                        <th>Horizon</th>
                        <th>n</th>
                        <th>MAE (m&#8486;)</th>
                        <th>RMSE (m&#8486;)</th>
                        <th>MAPE (%)</th>
                        <th>MAE persistence (m&#8486;)</th>
                        <th>Skill vs persistence</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($metrics as $h => $item)
                        @continue($selectedHorizon && $selectedHorizon !== $h)
                        <tr>
                            <td>H+{{ $h }}</td>
                            <td>{{ $item['n'] }}</td>
                            <td>{{ $fmt($item['mae'], 4) }}</td>
                            <td>{{ $fmt($item['rmse'], 4) }}</td>
                            <td>{{ $fmt($item['mape'], 2) }}</td>
                            <td>{{ $fmt($item['mae_persist'], 4) }}</td>
                            <td class="{{ is_null($item['skill']) ? '' : ($item['skill'] > 0 ? 'text-success' : 'text-danger') }}">
                                {{ is_null($item['skill']) ? '-' : sprintf('%+.1f%%', $item['skill']) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <small class="text-muted">
                Dihitung dari prediksi berkualitas OK yang nilai aktualnya sudah tersedia. Persistence: IR target dianggap sama dengan IR anchor.
                @if ($pending > 0) {{ $pending }} prediksi masih menunggu nilai aktual. @endif
            </small>
        </div>
    </div>

    <div class="card ai-card mb-4">
        <div class="card-header ai-head d-flex justify-content-between align-items-center flex-wrap">
            <span class="fw-bold"><i class="fas fa-table text-primary mr-1"></i> Tabulasi Prediksi vs Aktual</span>
            <small class="text-muted">
                {{ $selectedGardu?->nama ?? '-' }} | {{ \Carbon\Carbon::parse($fromDate)->format('d/m/Y') }} s/d {{ \Carbon\Carbon::parse($toDate)->format('d/m/Y') }} | {{ $rows->total() }} prediksi
            </small>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm text-center align-middle ai-tab-table">
                    <thead class="table-dark">
                        <tr>
                            <th>No</th>
                            <th>Tanggal Target</th>
                            <th>Tanggal Anchor</th>
                            <th>Horizon</th>
                            <th>Sel</th>
                            <th>Model</th>
                            <th>IR Anchor (m&#8486;)</th>
                            <th>IR Prediksi (m&#8486;)</th>
                            <th>IR Aktual (m&#8486;)</th>
                            <th>Selisih (m&#8486;)</th>
                            <th>Error (%)</th>
                            <th>Rentang 95%</th>
                            <th>Kualitas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td>{{ $rows->firstItem() + $loop->index }}</td>
                                <td>{{ $row['target_date']->format('d/m/Y') }}</td>
                                <td>{{ $row['anchor_date']->format('d/m/Y') }}</td>
                                <td>H+{{ $row['horizon'] }}</td>
                                <td>{{ $row['cell'] }}</td>
                                <td>{{ $row['model'] }}</td>
                                <td>{{ number_format($row['r_anchor'], 3) }}</td>
                                <td>{{ number_format($row['r_pred'], 4) }}</td>
                                @if (is_null($row['r_actual']))
                                    <td colspan="4"><span class="badge bg-light text-dark">Menunggu nilai aktual</span></td>
                                @else
                                    <td>{{ number_format($row['r_actual'], 3) }}</td>
                                    <td>{{ sprintf('%+.4f', $row['error']) }}</td>
                                    <td>{{ $fmt($row['error_pct'], 2) }}</td>
                                    <td>
                                        @if (is_null($row['in_band']))
                                            -
                                        @else
                                            <span class="badge {{ $row['in_band'] ? 'bg-success' : 'bg-warning text-dark' }}">{{ $row['in_band'] ? 'Di dalam' : 'Di luar' }}</span>
                                        @endif
                                    </td>
                                @endif
                                <td>
                                    @php [$label, $class] = $qualityText[$row['quality']] ?? [$row['quality'], 'bg-secondary']; @endphp
                                    <span class="badge {{ $class }}">{{ $label }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="text-center text-muted py-3">
                                    Belum ada prediksi dengan tanggal target pada rentang ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($rows->hasPages())
                <div class="d-flex justify-content-center mt-4">
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item {{ $rows->onFirstPage() ? 'disabled' : '' }}"><a class="page-link" href="{{ $rows->url(1) }}">First</a></li>
                        <li class="page-item {{ $rows->onFirstPage() ? 'disabled' : '' }}"><a class="page-link" href="{{ $rows->previousPageUrl() }}">Prev</a></li>
                        @foreach ($rows->getUrlRange(max(1, $rows->currentPage() - 2), min($rows->lastPage(), $rows->currentPage() + 2)) as $page => $url)
                            <li class="page-item {{ $page == $rows->currentPage() ? 'active' : '' }}"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                        @endforeach
                        <li class="page-item {{ $rows->hasMorePages() ? '' : 'disabled' }}"><a class="page-link" href="{{ $rows->nextPageUrl() }}">Next</a></li>
                        <li class="page-item {{ $rows->hasMorePages() ? '' : 'disabled' }}"><a class="page-link" href="{{ $rows->url($rows->lastPage()) }}">Last</a></li>
                    </ul>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('adding')
@include('partials.date-range-limit', ['maxDays' => \App\Http\Controllers\AiPredictionController::TABULATION_MAX_DAYS])
@endsection
