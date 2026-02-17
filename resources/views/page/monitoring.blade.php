@extends('layouts.master')

@section('title', 'Monitoring - '.$resor->nama)

@section('content')
<style>
    .monitoring-filter-card {
        border: 0;
        border-radius: 16px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
    }
    .monitoring-gardu-card {
        border: 0;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 10px 26px rgba(0, 0, 0, 0.08);
    }
    .monitoring-gardu-head {
        background: linear-gradient(90deg, #f8fbff, #edf6ff);
        border-bottom: 1px solid #deebf8;
    }
    .battery-tile {
        border: 1px solid #e6edf5;
        border-radius: 14px;
        transition: all 0.25s ease;
        background: #fff;
    }
    .battery-tile:hover {
        transform: translateY(-6px);
        box-shadow: 0 10px 24px rgba(0, 0, 0, 0.12);
        border-color: #b7d8f6;
    }
    .overview-tile {
        border: 1px solid #e6edf5;
        border-radius: 14px;
        transition: all 0.25s ease;
        background: #fff;
    }
    .overview-tile:hover {
        transform: translateY(-6px);
        box-shadow: 0 10px 24px rgba(0, 0, 0, 0.12);
        border-color: #b7d8f6;
    }
    .metric-item {
        background: #f5f9ff;
        border-radius: 10px;
        padding: 8px 10px;
        margin-bottom: 8px;
    }
    .metric-item:last-child {
        margin-bottom: 0;
    }
    .metric-icon {
        width: 24px;
        text-align: center;
        margin-right: 8px;
    }
    .badge-status {
        border-radius: 30px;
        font-size: 11px;
        padding: 6px 10px;
        white-space: normal;
        text-align: center;
    }
    .filter-inline {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: nowrap;
    }
    .filter-inline .form-label {
        margin-bottom: 0;
        white-space: nowrap;
    }
    .filter-inline .form-select {
        width: 320px;
        min-width: 260px;
    }
</style>

<div class="page-inner">
    <div class="card monitoring-filter-card mb-4">
        <div class="card-body">
            <form method="GET">
                <div class="filter-inline">
                    <label class="form-label fw-bold"><i class="fa fa-filter mr-1 text-primary"></i>Pilih Lokasi Gardu</label>
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
            $latestBatteryMonitorings = $g->batteries
                ->map(fn ($battery) => $battery->monitorings->sortByDesc('measured_at')->first())
                ->filter();

            $latestBattery = $latestBatteryMonitorings->sortByDesc('measured_at')->first();

            $latestChargerMonitoring = $g->chargers
                ->flatMap->monitorings
                ->sortByDesc('measured_at')
                ->first();

            $avgVolt = round($latestBatteryMonitorings->avg('volt') ?? 0, 2);
            $avgTemp = round($latestBatteryMonitorings->avg('temp') ?? 0, 2);
            $avgThd = round($latestBatteryMonitorings->avg('thd') ?? 0, 2);
            $avgSoc = round($latestBatteryMonitorings->avg('soc') ?? 0, 1);
            $avgSoh = round($latestBatteryMonitorings->avg('soh') ?? 0, 1);

            $statusClass = $avgSoc >= 80 ? 'success' : ($avgSoc >= 50 ? 'warning' : 'danger');
            $statusText = $avgSoc >= 80 ? 'Sehat' : ($avgSoc >= 50 ? 'Perlu Pantau' : 'Kritis');
        @endphp

        <div class="card monitoring-gardu-card mb-4">
            <div class="card-header monitoring-gardu-head">
                <div class="d-flex flex-wrap align-items-center justify-content-between">
                    <h5 class="card-title mb-0">
                        <i class="fa fa-industry text-primary mr-1"></i> {{ $g->nama }}
                        <small class="text-muted">({{ $g->n_batt }} Battery)</small>
                    </h5>
                    <div class="d-flex flex-wrap justify-content-end gap-2" style="gap: 6px;">
                        <span class="badge badge-light text-dark badge-status">
                            <i class="fa fa-charging-station mr-1 text-warning"></i> Volt Charge {{ $latestChargerMonitoring->voltage ?? '-' }} V
                        </span>
                        <span class="badge badge-light text-dark badge-status">
                            <i class="fa fa-tachometer-alt mr-1 text-secondary"></i> Current Charge {{ $latestChargerMonitoring->current ?? '-' }} A
                        </span>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="row">
                    @foreach ($g->batteries as $b)
                        @php
                            $last = $b->monitorings->sortByDesc('measured_at')->first();
                        @endphp

                        <div class="col-xl-2 col-lg-4 col-md- mb-3">
                            <div class="card battery-tile h-100">
                                <div class="card-header text-center bg-transparent border-0 pb-0">
                                    <strong><i class="fa fa-car-battery text-primary mr-1"></i> Battery {{ $loop->iteration }}</strong>
                                </div>

                                <div class="card-body text-center">
                                    <div class="metric-item d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0 d-flex align-items-center">
                                            <span class="metric-icon text-danger"><i class="fa fa-thermometer-half"></i></span>Temp
                                        </h6>
                                        <h6 class="mb-0">{{ $last->temp ?? '-' }} &deg;C</h6>
                                    </div>
                                    <div class="metric-item d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0 d-flex align-items-center">
                                            <span class="metric-icon text-warning"><i class="fa fa-bolt"></i></span>Volt
                                        </h6>
                                        <h6 class="mb-0">{{ $last->volt ?? '-' }} V</h6>
                                    </div>
                                    <div class="metric-item d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0 d-flex align-items-center">
                                            <span class="metric-icon text-secondary"><i class="fa fa-battery-half"></i></span>THD
                                        </h6>
                                        <h6 class="mb-0">{{ $last->thd ?? '-' }} m&#8486</h6>
                                    </div>
                                    <div class="metric-item d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0 d-flex align-items-center">
                                            <span class="metric-icon text-success"><i class="fa fa-car-battery"></i></span>SOC
                                        </h6>
                                        <h6 class="mb-0">{{ $last->soc ?? '-' }} %</h6>
                                    </div>
                                    <div class="metric-item d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0 d-flex align-items-center">
                                            <span class="metric-icon text-info"><i class="fa fa-car-battery"></i></span>SOH
                                        </h6>
                                        <h6 class="mb-0">{{ $last->soh ?? '-' }} %</h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <div class="col-xl-2 col-lg-3 col-md-4 mb-3">
                        <div class="card overview-tile h-100">
                            <div class="card-header text-center bg-transparent border-0 pb-0">
                                <strong><i class="fa fa-chart-pie text-primary mr-1"></i> Rata-rata Gardu</strong>
                            </div>
                            <div class="card-body text-center">
                                <div class="metric-item d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 d-flex align-items-center">
                                        <span class="metric-icon text-danger"><i class="fa fa-thermometer-half"></i></span>Temp
                                    </h6>
                                    <h6 class="mb-0">{{ $latestBatteryMonitorings->isNotEmpty() ? $avgTemp : '-' }} &deg;C</h6>
                                </div>
                                <div class="metric-item d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 d-flex align-items-center">
                                        <span class="metric-icon text-warning"><i class="fa fa-bolt"></i></span>Volt
                                    </h6>
                                    <h6 class="mb-0">{{ $latestBatteryMonitorings->isNotEmpty() ? $avgVolt : '-' }} V</h6>
                                </div>
                                <div class="metric-item d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 d-flex align-items-center">
                                        <span class="metric-icon text-secondary"><i class="fa fa-battery-half"></i></span>THD
                                    </h6>
                                    <h6 class="mb-0">{{ $latestBatteryMonitorings->isNotEmpty() ? $avgThd : '-' }} m&#8486</h6>
                                </div>
                                <div class="metric-item d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 d-flex align-items-center">
                                        <span class="metric-icon text-success"><i class="fa fa-car-battery"></i></span>SOC
                                    </h6>
                                    <h6 class="mb-0">{{ $latestBatteryMonitorings->isNotEmpty() ? $avgSoc : '-' }} %</h6>
                                </div>
                                <div class="metric-item d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 d-flex align-items-center">
                                        <span class="metric-icon text-info"><i class="fa fa-car-battery"></i></span>SOH
                                    </h6>
                                    <h6 class="mb-0">{{ $latestBatteryMonitorings->isNotEmpty() ? $avgSoh : '-' }} %</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer text-center">
                <p class="text-muted mt-2 mb-0" style="font-size: 12px">
                    <i class="fa fa-clock mr-1"></i>Updated {{ $latestBattery?->measured_at?->format('d/m/Y H:i') ?? '-' }}
                </p>
            </div>
        </div>
    @empty
        <div class="alert alert-warning">
            Tidak ada data gardu pada resor ini.
        </div>
    @endforelse
</div>
@endsection
