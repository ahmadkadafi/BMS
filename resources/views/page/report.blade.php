@extends('layouts.master')

@section('title', 'Report - '.$resor->nama)

@section('content')
<div class="page-inner">
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Bulan</label>
                    <select name="month" class="form-select">
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" @selected($month === $m)>
                                {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Tahun</label>
                    <select name="year" class="form-select">
                        @foreach ($years as $y)
                            <option value="{{ $y }}" @selected($year === $y)>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-primary w-100"><i class="fas fa-filter"></i> Tampilkan Report</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-4">
        @forelse ($reportCards as $card)
            @php
                $g = $card['gardu'];
                $remaining = $card['remaining_days'];
                $remainingLabel = is_null($remaining)
                    ? '-'
                    : ($remaining >= 0 ? $remaining.' hari' : 'Melewati '.abs($remaining).' hari');
                $remainingClass = is_null($remaining) ? 'secondary' : ($remaining < 90 ? 'danger' : ($remaining < 180 ? 'warning' : 'success'));
                $fmt = fn ($v, $unit = '') => is_null($v) ? '-' : number_format((float) $v, 2).($unit ? ' '.$unit : '');
            @endphp
            <div class="col-6 col-lg-4">
                <div class="card report-card border-0 shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0 fw-bold"><i class="fas fa-bolt text-primary me-2"></i>{{ $g->nama }}</h5>
                            <small class="text-muted">Kode: {{ $g->kode }} | Bulan {{ str_pad($month, 2, '0', STR_PAD_LEFT) }}/{{ $year }}</small>
                        </div>
                        <span class="badge bg-{{ $remainingClass }} px-3 py-2">
                            <i class="fas fa-hourglass-half me-1"></i> Sisa Umur Baterai: {{ $remainingLabel }}
                        </span>
                    </div>

                    <div class="card-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <div class="metric-tile warning-tile">
                                    <div class="icon-wrap"><i class="fas fa-exclamation-triangle"></i></div>
                                    <div>
                                        <div class="label">Jumlah Warning</div>
                                        <div class="value">{{ $card['warning_count'] }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="metric-tile alarm-tile">
                                    <div class="icon-wrap"><i class="fas fa-bell"></i></div>
                                    <div>
                                        <div class="label">Jumlah Alarm</div>
                                        <div class="value">{{ $card['alarm_count'] }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered align-middle text-center mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Variabel</th>
                                        <th>Nilai Minimum</th>
                                        <th>Nilai Maksimum</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="text-start"><i class="fas fa-thermometer-half text-danger me-2"></i>Temperature</td>
                                        <td>{{ $fmt($card['min_temp'], '°C') }}</td>
                                        <td>{{ $fmt($card['max_temp'], '°C') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-start"><i class="fas fa-car-battery text-success me-2"></i>Voltage Cell</td>
                                        <td>{{ $fmt($card['min_volt_cell'], 'V') }}</td>
                                        <td>{{ $fmt($card['max_volt_cell'], 'V') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-start"><i class="fas fa-wave-square text-info me-2"></i>THD</td>
                                        <td>{{ $fmt($card['min_thd'], '%') }}</td>
                                        <td>{{ $fmt($card['max_thd'], '%') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-start"><i class="fas fa-charging-station text-primary me-2"></i>Voltage Charge</td>
                                        <td>{{ $fmt($card['min_voltage_charge'], 'V') }}</td>
                                        <td>{{ $fmt($card['max_voltage_charge'], 'V') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-start"><i class="fas fa-bolt text-warning me-2"></i>Current Charge</td>
                                        <td>{{ $fmt($card['min_current_charge'], 'A') }}</td>
                                        <td>{{ $fmt($card['max_current_charge'], 'A') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info mb-0">Belum ada data gardu untuk resor ini.</div>
            </div>
        @endforelse
    </div>
</div>
@endsection

@section('adding')
<style>
.report-card .card-header {
    background: linear-gradient(120deg, #f6f9ff 0%, #eef3ff 100%);
}

.metric-tile {
    border-radius: 12px;
    padding: 12px 14px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.metric-tile .icon-wrap {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

.metric-tile .label {
    font-size: 13px;
    color: #6b7280;
}

.metric-tile .value {
    font-size: 24px;
    font-weight: 700;
    line-height: 1;
}

.warning-tile {
    background: #fff8e6;
    border: 1px solid #ffe4a6;
}

.warning-tile .icon-wrap {
    background: #ffe8b8;
    color: #9a6700;
}

.alarm-tile {
    background: #fff0f2;
    border: 1px solid #ffd1d8;
}

.alarm-tile .icon-wrap {
    background: #ffd7de;
    color: #b42318;
}
</style>
@endsection
