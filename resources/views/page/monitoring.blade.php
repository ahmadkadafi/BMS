@extends('layouts.master')

@section('title', 'Monitoring - '.$resor->nama)

@section('content')
<style>
    .monitoring-filter-card {
        border: 0;
        border-radius: 16px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
    }
    .monitoring-card {
        border: 0;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 14px 35px rgba(15, 23, 42, 0.08);
    }
    .monitoring-card .card-header {
        background: linear-gradient(90deg, #eef4ff, #f8fbff);
        border-bottom: 1px solid #dce9f8;
    }
    .monitoring-summary-card,
    .monitoring-detail-card {
        border: 1px solid #e6edf5;
        border-radius: 16px;
        background: #ffffff;
    }
    .monitoring-summary-card {
        padding: 20px;
        min-height: 180px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .monitoring-summary-card h5 {
        margin: 0;
        font-size: 1rem;
        color: #334155;
    }
    .monitoring-summary-card .metric-value {
        font-size: 2.3rem;
        font-weight: 700;
        margin: 1rem 0 0.25rem;
    }
    .monitoring-detail-card {
        padding: 22px;
    }
    .monitoring-detail-card h5 {
        margin-bottom: 18px;
        font-size: 1.05rem;
        color: #0f172a;
    }
    .metric-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 14px 16px;
        margin-bottom: 12px;
        border-radius: 14px;
        background: #f8fafc;
    }
    .metric-row span {
        color: #64748b;
    }
    .metric-row strong {
        color: #0f172a;
    }
    .badge-status {
        border-radius: 999px;
        padding: 8px 14px;
        font-size: 0.85rem;
        font-weight: 600;
    }
    .badge-online { background: #22c55e; color: white; }
    .badge-offline { background: #ef4444; color: white; }
    .badge-warning { background: #f59e0b; color: white; }
    .filter-inline {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }
    .filter-inline .form-label {
        margin-bottom: 0;
        white-space: nowrap;
        font-weight: 600;
    }
    .filter-inline .form-select {
        width: 320px;
        min-width: 240px;
    }
    .status-label {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
    }
    .status-bullet {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
    }
    .status-bullet.online { background: #22c55e; }
    .status-bullet.offline { background: #ef4444; }
    .status-bullet.warning { background: #f59e0b; }
</style>

<div class="page-inner">
    <div class="card monitoring-filter-card mb-4">
        <div class="card-body">
            <form method="GET">
                <div class="filter-inline">
                    <label class="form-label"><i class="fa fa-filter text-primary me-2"></i>Pilih Lokasi Gardu</label>
                    <select name="gardu_id" class="form-select" onchange="this.form.submit()">
                        <option value="">-- Semua Gardu --</option>
                        @foreach ($gardu as $g)
                            <option value="{{ $g->id }}" {{ request('gardu_id') == $g->id ? 'selected' : '' }}>
                                {{ $g->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    @php
        $filteredGardu = request('gardu_id') ? $gardu->where('id', request('gardu_id')) : $gardu;
    @endphp

    @forelse ($filteredGardu as $g)
        @php
            $dummy = $dummyMonitoring[$g->id] ?? [
                'battery' => ['level' => 80, 'health' => 'Baik', 'voltage' => 50, 'temp' => 26, 'soc' => 78, 'soh' => 83],
                'pln' => ['status' => 'ONLINE', 'voltage' => 220, 'frequency' => 50, 'load' => '3.8 kW', 'relay' => 'Normal'],
                'microwave' => ['link' => 'Tersambung', 'latency' => 14, 'quality' => '86%', 'tx' => -18, 'rx' => -22, 'signal' => '-68'],
                'baseStation' => ['status' => 'Aktif', 'traffic' => '35.2 Mbps', 'rfPower' => -46, 'signal' => '-71', 'maintenance' => 'Normal'],
            ];

            $latestBatteryMonitorings = $g->batteries
                ->map(fn ($battery) => $battery->monitorings->sortByDesc('measured_at')->first())
                ->filter();

            $latestBattery = $latestBatteryMonitorings->sortByDesc('measured_at')->first();

            $latestChargerMonitoring = $g->chargers
                ->flatMap->monitorings
                ->sortByDesc('measured_at')
                ->first();
        @endphp

        <div class="card monitoring-card mb-5">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h4 class="mb-1">Monitoring Gardu {{ $g->nama }}</h4>
                    <p class="text-muted mb-0">Resor {{ $resor->nama }} · {{ $g->n_batt }} Battery</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge-status {{ $dummy['pln']['status'] === 'ONLINE' ? 'badge-online' : 'badge-offline' }}">
                        PLN {{ $dummy['pln']['status'] }}
                    </span>
                    <span class="badge-status {{ $dummy['microwave']['link'] === 'Tersambung' ? 'badge-online' : 'badge-warning' }}">
                        Microwave {{ $dummy['microwave']['link'] }}
                    </span>
                    <span class="badge-status {{ $dummy['baseStation']['status'] === 'Aktif' ? 'badge-online' : 'badge-warning' }}">
                        Base Station {{ $dummy['baseStation']['status'] }}
                    </span>
                </div>
            </div>

            <div class="card-body">
                <div class="row g-4">
                    <div class="col-lg-3 col-md-6">
                        <div class="monitoring-summary-card">
                            <div>
                                <h5>Battery</h5>
                                <div class="metric-value">{{ $dummy['battery']['level'] }}%</div>
                            </div>
                            <div>
                                <p class="mb-1">Health: {{ $dummy['battery']['health'] }}</p>
                                <p class="mb-1">Voltage: {{ $dummy['battery']['voltage'] }} V</p>
                                <p class="mb-0">Temperatur: {{ $dummy['battery']['temp'] }} °C</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="monitoring-summary-card">
                            <div>
                                <h5>PLN</h5>
                                <div class="metric-value">{{ $dummy['pln']['voltage'] }} V</div>
                            </div>
                            <div>
                                <p class="mb-1">Status: {{ $dummy['pln']['status'] }}</p>
                                <p class="mb-1">Frekuensi: {{ $dummy['pln']['frequency'] }} Hz</p>
                                <p class="mb-0">Beban: {{ $dummy['pln']['load'] }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="monitoring-summary-card">
                            <div>
                                <h5>Microwave</h5>
                                <div class="metric-value">{{ $dummy['microwave']['quality'] }}</div>
                            </div>
                            <div>
                                <p class="mb-1">Link: {{ $dummy['microwave']['link'] }}</p>
                                <p class="mb-1">Latency: {{ $dummy['microwave']['latency'] }} ms</p>
                                <p class="mb-0">Sinyal: {{ $dummy['microwave']['signal'] }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="monitoring-summary-card">
                            <div>
                                <h5>Base Station</h5>
                                <div class="metric-value">{{ $dummy['baseStation']['traffic'] }}</div>
                            </div>
                            <div>
                                <p class="mb-1">Status: {{ $dummy['baseStation']['status'] }}</p>
                                <p class="mb-1">RF Power: {{ $dummy['baseStation']['rfPower'] }} dBm</p>
                                <p class="mb-0">Maintenance: {{ $dummy['baseStation']['maintenance'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mt-3">
                    <div class="col-lg-6">
                        <div class="monitoring-detail-card">
                            <h5>Detail Battery</h5>
                            <div class="metric-row">
                                <span>State of Charge</span>
                                <strong>{{ $dummy['battery']['soc'] }}%</strong>
                            </div>
                            <div class="metric-row">
                                <span>State of Health</span>
                                <strong>{{ $dummy['battery']['soh'] }}%</strong>
                            </div>
                            <div class="metric-row">
                                <span>Tegangan</span>
                                <strong>{{ $dummy['battery']['voltage'] }} V</strong>
                            </div>
                            <div class="metric-row">
                                <span>Temperatur</span>
                                <strong>{{ $dummy['battery']['temp'] }} °C</strong>
                            </div>
                            <div class="metric-row">
                                <span>Kondisi</span>
                                <strong>{{ $dummy['battery']['health'] }}</strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="monitoring-detail-card">
                            <h5>Detail Sistem Listrik</h5>
                            <div class="metric-row">
                                <span>Status PLN</span>
                                <strong>{{ $dummy['pln']['status'] }}</strong>
                            </div>
                            <div class="metric-row">
                                <span>Tegangan PLN</span>
                                <strong>{{ $dummy['pln']['voltage'] }} V</strong>
                            </div>
                            <div class="metric-row">
                                <span>Frekuensi</span>
                                <strong>{{ $dummy['pln']['frequency'] }} Hz</strong>
                            </div>
                            <div class="metric-row">
                                <span>Beban</span>
                                <strong>{{ $dummy['pln']['load'] }}</strong>
                            </div>
                            <div class="metric-row">
                                <span>Relay</span>
                                <strong>{{ $dummy['pln']['relay'] }}</strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="monitoring-detail-card">
                            <h5>Detail Radio Microwave</h5>
                            <div class="metric-row">
                                <span>Status Link</span>
                                <strong>{{ $dummy['microwave']['link'] }}</strong>
                            </div>
                            <div class="metric-row">
                                <span>Latency</span>
                                <strong>{{ $dummy['microwave']['latency'] }} ms</strong>
                            </div>
                            <div class="metric-row">
                                <span>Kualitas Link</span>
                                <strong>{{ $dummy['microwave']['quality'] }}</strong>
                            </div>
                            <div class="metric-row">
                                <span>Power TX</span>
                                <strong>{{ $dummy['microwave']['tx'] }} dBm</strong>
                            </div>
                            <div class="metric-row">
                                <span>Power RX</span>
                                <strong>{{ $dummy['microwave']['rx'] }} dBm</strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="monitoring-detail-card">
                            <h5>Detail Base Station</h5>
                            <div class="metric-row">
                                <span>Status Operasi</span>
                                <strong>{{ $dummy['baseStation']['status'] }}</strong>
                            </div>
                            <div class="metric-row">
                                <span>Traffic</span>
                                <strong>{{ $dummy['baseStation']['traffic'] }}</strong>
                            </div>
                            <div class="metric-row">
                                <span>RF Power</span>
                                <strong>{{ $dummy['baseStation']['rfPower'] }} dBm</strong>
                            </div>
                            <div class="metric-row">
                                <span>Sinyal</span>
                                <strong>{{ $dummy['baseStation']['signal'] }} dBm</strong>
                            </div>
                            <div class="metric-row">
                                <span>Maintenance</span>
                                <strong>{{ $dummy['baseStation']['maintenance'] }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer text-muted d-flex justify-content-between align-items-center">
                <div>
                    <span class="status-label">
                        <span class="status-bullet {{ $dummy['pln']['status'] === 'ONLINE' ? 'online' : 'offline' }}"></span>
                        Sistem monitoring dummy
                    </span>
                </div>
                <small>Updated {{ $latestBattery?->measured_at?->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i') }}</small>
            </div>
        </div>
    @empty
        <div class="alert alert-warning">
            Tidak ada data gardu pada resor ini.
        </div>
    @endforelse
</div>
@endsection
