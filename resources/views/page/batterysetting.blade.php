@extends('layouts.master')

@section('title', 'Battery Setting - '.$resor->nama)

@section('content')
<div class="page-inner">

    

    {{-- ================= TABLE ================= --}}
    <div class="card">
        <div class="card-header">
            <div class="d-flex">
                <i class="fa-solid fa-gear"></i>
                <h6 class="fw-bold mb-0">Battery Setting</h6>
            </div>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered align-middle text-center">
                <thead class="table-dark text-center align-middle">
                    {{-- ROW 1 --}}
                    <tr>
                        <th rowspan="3">No</th>
                        <th rowspan="3">Gardu</th>
                        <th colspan="4">Voltage (V)</th>
                        <th colspan="4">Temperature (°C)</th>
                        <th colspan="2">THD (%)</th>
                        <th colspan="2">SOC (%)</th>
                        <th colspan="2">SOH (%)</th>
                        <th rowspan="3">Action</th>
                    </tr>

                    {{-- ROW 2 --}}
                    <tr>
                        <th colspan="2">Min</th>
                        <th colspan="2">Max</th>
                        <th colspan="2">Min</th>
                        <th colspan="2">Max</th>
                        <th rowspan="2">Warn</th>
                        <th rowspan="2">Alarm</th>
                        <th rowspan="2">Warn</th>
                        <th rowspan="2">Alarm</th>
                        <th rowspan="2">Warn</th>
                        <th rowspan="2">Alarm</th>
                    </tr>

                    {{-- ROW 3 --}}
                    <tr>
                        <th>Warn</th>
                        <th>Alarm</th>
                        <th>Warn</th>
                        <th>Alarm</th>
                        <th>Warn</th>
                        <th>Alarm</th>
                        <th>Warn</th>
                        <th>Alarm</th>
                    </tr>
                </thead>


                <tbody>
                @foreach ($gardu as $g)
                    @php
                        $s = $g->batterySetting;
                    @endphp
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td class="fw-semibold">{{ $g->nama }}</td>

                        <td>{{ $s->volt_min_warn  ?? '-' }}</td>
                        <td>{{ $s->volt_min_alarm ?? '-' }}</td>
                        <td>{{ $s->volt_max_warn  ?? '-' }}</td>
                        <td>{{ $s->volt_max_alarm ?? '-' }}</td>

                        <td>{{ $s->temp_min_warn  ?? '-' }}</td>
                        <td>{{ $s->temp_min_alarm ?? '-' }}</td>
                        <td>{{ $s->temp_max_warn  ?? '-' }}</td>
                        <td>{{ $s->temp_max_alarm ?? '-' }}</td>

                        <td>{{ $s->thd_warn ?? '-' }}</td>
                        <td>{{ $s->thd_alarm ?? '-' }}</td>

                        <td>{{ $s->soc_warn ?? '-' }}</td>
                        <td>{{ $s->soc_alarm ?? '-' }}</td>

                        <td>{{ $s->soh_warn ?? '-' }}</td>
                        <td>{{ $s->soh_alarm ?? '-' }}</td>

                        {{-- ===== ACTION ===== --}}
                        <td>
                            <div class="d-flex justify-content-center gap-2">

                                {{-- EDIT --}}
                                <button class="btn btn-warning btn-icon btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#edit{{ $g->id }}"
                                        title="Edit Setting">
                                    <i class="fas fa-edit"></i>
                                </button>

                                {{-- DELETE --}}
                                @if($s)
                                <button class="btn btn-danger btn-icon btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#delete{{ $g->id }}"
                                        title="Hapus Setting">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                                @endif

                            </div>
                        </td>
                    </tr>

                    {{-- ================= EDIT MODAL ================= --}}
                    <div class="modal fade" id="edit{{ $g->id }}" tabindex="-1">
                        <div class="modal-dialog modal-xl modal-dialog-centered">
                            <form method="POST"
                                  action="{{ route('batterysetting.store') }}">
                                @csrf

                                <input type="hidden" name="gardu_id" value="{{ $g->id }}">

                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">
                                            Edit Battery Setting – {{ $g->nama }}
                                        </h5>
                                        <button type="button"
                                                class="btn-close"
                                                data-bs-dismiss="modal"></button>
                                    </div>

                                    <div class="modal-body">

                                        {{-- VOLTAGE --}}
                                        <h6 class="fw-bold mb-3">Voltage (V)</h6>
                                        <div class="row g-3 mb-4">
                                            @foreach ([
                                                'volt_min_warn'=>'Min Warn',
                                                'volt_min_alarm'=>'Min Alarm',
                                                'volt_max_warn'=>'Max Warn',
                                                'volt_max_alarm'=>'Max Alarm'
                                            ] as $f=>$l)
                                            <div class="col-md-3">
                                                <label>{{ $l }}</label>
                                                <input type="number" step="0.01"
                                                       name="{{ $f }}"
                                                       class="form-control"
                                                       value="{{ $s->$f ?? '' }}">
                                            </div>
                                            @endforeach
                                        </div>

                                        {{-- TEMPERATURE --}}
                                        <h6 class="fw-bold mb-3">Temperature (°C)</h6>
                                        <div class="row g-3 mb-4">
                                            @foreach ([
                                                'temp_min_warn'=>'Min Warn',
                                                'temp_min_alarm'=>'Min Alarm',
                                                'temp_max_warn'=>'Max Warn',
                                                'temp_max_alarm'=>'Max Alarm'
                                            ] as $f=>$l)
                                            <div class="col-md-3">
                                                <label>{{ $l }}</label>
                                                <input type="number" step="0.1"
                                                       name="{{ $f }}"
                                                       class="form-control"
                                                       value="{{ $s->$f ?? '' }}">
                                            </div>
                                            @endforeach
                                        </div>

                                        {{-- THD --}}
                                        <h6 class="fw-bold mb-3">THD (%)</h6>
                                        <div class="row g-3 mb-4">
                                            @foreach (['thd_warn'=>'Warn','thd_alarm'=>'Alarm'] as $f=>$l)
                                            <div class="col-md-6">
                                                <label>{{ $l }}</label>
                                                <input type="number" step="0.01"
                                                       name="{{ $f }}"
                                                       class="form-control"
                                                       value="{{ $s->$f ?? '' }}">
                                            </div>
                                            @endforeach
                                        </div>

                                        {{-- SOC --}}
                                        <h6 class="fw-bold mb-3">SOC (%)</h6>
                                        <div class="row g-3 mb-4">
                                            @foreach (['soc_warn'=>'Warn','soc_alarm'=>'Alarm'] as $f=>$l)
                                            <div class="col-md-6">
                                                <label>{{ $l }}</label>
                                                <input type="number" step="0.01"
                                                       name="{{ $f }}"
                                                       class="form-control"
                                                       value="{{ $s->$f ?? '' }}">
                                            </div>
                                            @endforeach
                                        </div>

                                        {{-- SOH --}}
                                        <h6 class="fw-bold mb-3">SOH (%)</h6>
                                        <div class="row g-3">
                                            @foreach (['soh_warn'=>'Warn','soh_alarm'=>'Alarm'] as $f=>$l)
                                            <div class="col-md-6">
                                                <label>{{ $l }}</label>
                                                <input type="number" step="0.01"
                                                       name="{{ $f }}"
                                                       class="form-control"
                                                       value="{{ $s->$f ?? '' }}">
                                            </div>
                                            @endforeach
                                        </div>

                                    </div>

                                    <div class="modal-footer">
                                        <button type="button"
                                                class="btn btn-secondary"
                                                data-bs-dismiss="modal">
                                            Batal
                                        </button>
                                        <button class="btn btn-primary">
                                            Simpan Setting
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- ================= DELETE MODAL ================= --}}
                    @if($s)
                    <div class="modal fade" id="delete{{ $g->id }}" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <form method="POST"
                                  action="{{ route('batterysetting.destroy', $g->id) }}">
                                @csrf
                                @method('DELETE')

                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title text-danger">
                                            Hapus Battery Setting
                                        </h5>
                                        <button type="button"
                                                class="btn-close"
                                                data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        Yakin hapus setting untuk
                                        <strong>{{ $g->nama }}</strong>?
                                    </div>
                                    <div class="modal-footer">
                                        <button class="btn btn-secondary"
                                                data-bs-dismiss="modal">
                                            Batal
                                        </button>
                                        <button class="btn btn-danger">
                                            Hapus
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    @endif

                @endforeach
                </tbody>
            </table>

        </div>
    </div>

</div>
@endsection

@section('adding')
<style>
.btn-icon{
    width:36px;
    height:36px;
    padding:0;
    display:flex;
    align-items:center;
    justify-content:center;
}
.table th,.table td{
    white-space:nowrap;
    font-size:13px;
}
</style>
@endsection
