@extends('layouts.master')

@section('title', 'AI Prediction - '.($resor?->nama ?? 'Semua Data'))

@section('content')
@php
    $badge = [
        'Normal' => 'bg-success',
        'Warning' => 'bg-warning text-dark',
        'Alarm' => 'bg-danger',
    ];
    $reasonText = [
        'insufficient_history' => 'Riwayat berurutan belum cukup',
        'data_gap' => 'Ada data kosong di dalam window',
    ];
    $hasForecast = ! empty($chart['labels']);
    $formUrl = $resor ? route('ai-prediction.resor', ['resor' => $resor->id]) : route('ai-prediction.all');
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
    .ai-stat-label {
        font-size: 12px;
        color: #6c757d;
    }
    .ai-stat-value {
        font-size: 24px;
        font-weight: 700;
        line-height: 1.2;
    }
    .ai-stat-note {
        font-size: 12px;
        color: #6c757d;
    }
    .ai-chart-box {
        height: 400px;
    }
</style>

<div class="page-inner">
    <div class="card ai-card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ $formUrl }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-bold"><i class="fas fa-map-marker-alt text-primary mr-1"></i> Lokasi</label>
                        <select name="gardu_id" class="form-select" onchange="this.form.submit()">
                            @foreach ($gardu as $g)
                                <option value="{{ $g->id }}" {{ $selectedGardu?->id === $g->id ? 'selected' : '' }}>{{ $g->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold"><i class="fas fa-battery-half text-primary mr-1"></i> Sel</label>
                        <select name="cell" class="form-select" onchange="this.form.submit()">
                            <option value="">Rata-rata semua sel</option>
                            @foreach ($cells as $id => $label)
                                <option value="{{ $id }}" {{ $selectedCell === $id ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold"><i class="fas fa-calendar-check text-primary mr-1"></i> Prediksi dari tanggal</label>
                        <select name="anchor" class="form-select" onchange="this.form.submit()" {{ $anchors->isEmpty() ? 'disabled' : '' }}>
                            @forelse ($anchors as $anchor)
                                <option value="{{ $anchor }}" {{ $selectedAnchor === $anchor ? 'selected' : '' }}>{{ \Carbon\Carbon::parse($anchor)->format('d/m/Y') }}</option>
                            @empty
                                <option>Belum ada prediksi</option>
                            @endforelse
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if (! $isValidated)
        @php
            $tested = collect($backtest)->filter(fn ($skill) => ! is_null($skill));
            $losing = $tested->filter(fn ($skill) => $skill <= 0);
        @endphp
        <div class="alert {{ $tested->isEmpty() || $losing->isNotEmpty() ? 'alert-warning' : 'alert-info' }}">
            <strong>Belum divalidasi.</strong> Model dilatih hanya dengan data Gardu Cilejit.
            @if ($tested->isEmpty())
                Backtest {{ $selectedGardu?->nama }} tidak menghasilkan window yang lolos pemeriksaan kualitas, sehingga keandalannya tidak diketahui.
            @elseif ($losing->isEmpty())
                Pada backtest {{ $selectedGardu?->nama }} model lebih akurat daripada persistence di semua horizon yang diuji.
            @else
                Pada backtest {{ $selectedGardu?->nama }} model kalah dari persistence pada {{ $losing->keys()->map(fn ($h) => 'H+'.$h)->join(', ') }}; prediksi horizon tersebut jangan dijadikan acuan.
            @endif
        </div>
    @endif
    @if (($latest['stale_days'] ?? 0) >= 2)
        <div class="alert alert-warning">
            <strong>Data monitoring tidak mutakhir.</strong> Pembacaan terakhir {{ $latest['measured_at'] }} ({{ $latest['stale_days'] }} hari lalu).
        </div>
    @endif
    @if ($quality['ir_frozen'] > 0)
        <div class="alert alert-danger">
            <strong>Prediksi tidak dapat dipercaya.</strong> IR {{ $quality['ir_frozen'] }} sel tidak berubah selama {{ $quality['flat_days'] }} hari, sedangkan pada data latih paling lama 6 hari. Periksa pengukuran IR pada BMS gardu ini.
        </div>
    @endif
    @if ($quality['ood'] > 0)
        <div class="alert alert-danger">
            <strong>Di luar rentang data latih.</strong> Input {{ $quality['ood'] }} sel jauh dari kondisi pelatihan (|z| maks. {{ $quality['max_z'] }}), sehingga prediksi tidak dapat dipercaya.
        </div>
    @endif
    @if ($lastRun?->status === 'failed')
        <div class="alert alert-danger">
            <strong>Proses forecast terakhir gagal.</strong> Lihat kolom <code>error</code> pada tabel <code>ir_forecast_run</code>.
        </div>
    @endif

    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card ai-card h-100">
                <div class="card-body">
                    <div class="ai-stat-label">IR terkini (rata-rata sel)</div>
                    <div class="ai-stat-value" id="aiLatestIr">{{ $latest ? number_format($latest['ir'], 2) : '-' }} <small>m&#8486;</small></div>
                    <div class="ai-stat-note" id="aiLatestTime">{{ $latest['measured_at'] ?? 'Belum ada data' }}</div>
                </div>
            </div>
        </div>
        @foreach ($horizons as $h)
            <div class="col-md-3 mb-4">
                <div class="card ai-card h-100">
                    <div class="card-body">
                        <div class="ai-stat-label">Prediksi H+{{ $h }}{{ $summary[$h] ? ' ('.$summary[$h]['target'].')' : '' }}</div>
                        @if ($summary[$h])
                            <div class="ai-stat-value">{{ number_format($summary[$h]['r_pred'], 3) }} <small>m&#8486;</small></div>
                            <div class="ai-stat-note">
                                {{ sprintf('%+.3f', $summary[$h]['delta']) }} m&#8486;
                                @if (! is_null($summary[$h]['band']))
                                    &middot; &plusmn;{{ number_format($summary[$h]['band'], 3) }}
                                @endif
                            </div>
                            @if (! is_null($backtest[$h] ?? null))
                                <div class="ai-stat-note {{ $backtest[$h] > 0 ? 'text-success' : 'text-danger' }}">
                                    Skill backtest {{ sprintf('%+.1f', $backtest[$h]) }}%
                                </div>
                            @endif
                        @else
                            <div class="ai-stat-value">-</div>
                            <div class="ai-stat-note">Belum tersedia</div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if (! empty($readiness))
        <div class="card ai-card mb-4">
            <div class="card-header ai-head">
                <span class="fw-bold"><i class="fas fa-hourglass-half text-primary mr-1"></i> Kesiapan Model</span>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr><th>Horizon</th><th>Model</th><th>Keterangan</th><th>Riwayat</th><th>Perkiraan siap</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($readiness as $item)
                            <tr>
                                <td>H+{{ $item['horizon'] }}</td>
                                <td>{{ $item['model'] }}</td>
                                <td>{{ $reasonText[$item['reason']] ?? $item['reason'] }}</td>
                                <td>{{ $item['available'] }} / {{ $item['required'] }} hari</td>
                                <td>{{ $item['ready_at'] ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="card ai-card mb-4">
        <div class="card-header ai-head d-flex justify-content-between align-items-center flex-wrap">
            <span class="fw-bold"><i class="fas fa-brain text-primary mr-1"></i> Forecast Internal Resistance</span>
            <small class="text-muted">
                {{ $selectedGardu?->nama ?? '-' }} | {{ $selectedCell ? $cells[$selectedCell] : 'Rata-rata semua sel' }}
                @if ($selectedAnchor) | hari anchor {{ \Carbon\Carbon::parse($selectedAnchor)->format('d/m/Y') }} @endif
            </small>
        </div>
        <div class="card-body">
            @if ($hasForecast)
                <div class="ai-chart-box"><canvas id="aiChart"></canvas></div>
            @else
                <div class="text-center text-muted py-5">
                    Belum ada prediksi untuk gardu ini. Jalankan <code>php artisan ai:forecast</code> setelah riwayat harian mencukupi.
                </div>
            @endif
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7 mb-4">
            <div class="card ai-card h-100">
                <div class="card-header ai-head">
                    <span class="fw-bold"><i class="fas fa-table text-primary mr-1"></i> Prediksi per Sel</span>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Sel</th>
                                <th>IR anchor</th>
                                @foreach ($horizons as $h)
                                    <th>H+{{ $h }}</th>
                                @endforeach
                                <th>Status horizon terjauh</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr>
                                    <td>{{ $row['label'] }}</td>
                                    <td>{{ $row['r_anchor'] ?? '-' }}</td>
                                    @foreach ($horizons as $h)
                                        <td>{{ $row['predictions'][$h] ? number_format($row['predictions'][$h]->r_pred, 3) : '-' }}</td>
                                    @endforeach
                                    <td>
                                        @if (in_array($row['quality'], ['ir_frozen', 'ood'], true))
                                            <span class="badge bg-secondary">Tidak andal</span>
                                        @elseif (isset($badge[$row['status']]))
                                            <span class="badge {{ $badge[$row['status']] }}">{{ $row['status'] }}</span>
                                        @else
                                            <span class="text-muted">{{ $row['status'] }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-5 mb-4">
            <div class="card ai-card h-100">
                <div class="card-header ai-head">
                    <span class="fw-bold"><i class="fas fa-bullseye text-primary mr-1"></i> Akurasi Prediksi vs Realisasi</span>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-sm mb-2">
                        <thead>
                            <tr><th>H</th><th>n</th><th>Model</th><th>Persistence</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($accuracy as $h => $item)
                                <tr>
                                    <td>H+{{ $h }}</td>
                                    <td>{{ $item['n'] }}</td>
                                    <td>{{ is_null($item['mae']) ? '-' : number_format($item['mae'], 4) }}</td>
                                    <td>{{ is_null($item['mae_persist']) ? '-' : number_format($item['mae_persist'], 4) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <small class="text-muted">MAE dalam m&#8486;. Hanya prediksi berkualitas baik yang tanggal targetnya sudah terlewati.</small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('adding')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const aiChart = @json($chart);
const aiStatusUrl = @json($statusUrl);
let aiVersion = @json($lastRun?->id);

if (aiChart.labels.length) {
    const hasBand = aiChart.high.some(value => value !== null);
    const datasets = [
        { label: 'Aktual', data: aiChart.actual, borderColor: '#212529', backgroundColor: '#212529', borderWidth: 2, pointRadius: 0, spanGaps: true },
        { label: 'Prediksi', data: aiChart.forecast, borderColor: '#dc3545', backgroundColor: '#dc3545', borderWidth: 2, borderDash: [6, 4], pointRadius: 4, spanGaps: true },
    ];

    if (hasBand) {
        datasets.push(
            { label: 'Batas bawah 95%', data: aiChart.low, borderColor: 'transparent', pointRadius: 0, spanGaps: true, fill: false },
            { label: 'Rentang 95%', data: aiChart.high, borderColor: 'transparent', backgroundColor: 'rgba(220, 53, 69, 0.15)', pointRadius: 0, spanGaps: true, fill: '-1' }
        );
    }

    new Chart(document.getElementById('aiChart'), {
        type: 'line',
        data: { labels: aiChart.labels, datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { labels: { filter: item => item.text !== 'Batas bawah 95%' } },
            },
            scales: {
                x: { ticks: { maxTicksLimit: 12 } },
                y: { title: { display: true, text: 'Internal resistance (mΩ)' } },
            },
        },
    });
}

async function refreshAiStatus() {
    try {
        const response = await fetch(aiStatusUrl, { headers: { Accept: 'application/json' } });
        if (!response.ok) {
            return;
        }
        const status = await response.json();
        if (status.version !== aiVersion && status.version !== null) {
            window.location.reload();
            return;
        }
        if (status.latest) {
            document.getElementById('aiLatestIr').firstChild.textContent = status.latest.ir.toFixed(2) + ' ';
            document.getElementById('aiLatestTime').textContent = status.latest.measured_at;
        }
    } catch (error) {
        console.warn('Gagal memuat status AI Prediction', error);
    }
}

setInterval(refreshAiStatus, 60000);
</script>
@endsection
