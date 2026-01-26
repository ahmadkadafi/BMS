<div class="modal fade" id="edit{{ $gardu->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <form method="POST" action="{{ route('batterysetting.store') }}">
            @csrf

            <input type="hidden" name="gardu_id" value="{{ $gardu->id }}">

            <div class="modal-content">

                {{-- HEADER --}}
                <div class="modal-header">
                    <h5 class="modal-title">
                        Edit Battery Setting – {{ $gardu->nama }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                {{-- BODY --}}
                <div class="modal-body">

                    {{-- VOLTAGE --}}
                    <h6 class="fw-bold mb-3">Voltage (V)</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label>Min Warn</label>
                            <input type="number" step="0.01" name="volt_min_warn"
                                   class="form-control"
                                   value="{{ $gardu->batterySetting->volt_min_warn ?? '' }}">
                        </div>
                        <div class="col-md-3">
                            <label>Min Alarm</label>
                            <input type="number" step="0.01" name="volt_min_alarm"
                                   class="form-control"
                                   value="{{ $gardu->batterySetting->volt_min_alarm ?? '' }}">
                        </div>
                        <div class="col-md-3">
                            <label>Max Warn</label>
                            <input type="number" step="0.01" name="volt_max_warn"
                                   class="form-control"
                                   value="{{ $gardu->batterySetting->volt_max_warn ?? '' }}">
                        </div>
                        <div class="col-md-3">
                            <label>Max Alarm</label>
                            <input type="number" step="0.01" name="volt_max_alarm"
                                   class="form-control"
                                   value="{{ $gardu->batterySetting->volt_max_alarm ?? '' }}">
                        </div>
                    </div>

                    {{-- TEMPERATURE --}}
                    <h6 class="fw-bold mb-3">Temperature (°C)</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label>Min Warn</label>
                            <input type="number" step="0.1" name="temp_min_warn"
                                   class="form-control"
                                   value="{{ $gardu->batterySetting->temp_min_warn ?? '' }}">
                        </div>
                        <div class="col-md-3">
                            <label>Min Alarm</label>
                            <input type="number" step="0.1" name="temp_min_alarm"
                                   class="form-control"
                                   value="{{ $gardu->batterySetting->temp_min_alarm ?? '' }}">
                        </div>
                        <div class="col-md-3">
                            <label>Max Warn</label>
                            <input type="number" step="0.1" name="temp_max_warn"
                                   class="form-control"
                                   value="{{ $gardu->batterySetting->temp_max_warn ?? '' }}">
                        </div>
                        <div class="col-md-3">
                            <label>Max Alarm</label>
                            <input type="number" step="0.1" name="temp_max_alarm"
                                   class="form-control"
                                   value="{{ $gardu->batterySetting->temp_max_alarm ?? '' }}">
                        </div>
                    </div>

                    {{-- THD --}}
                    <h6 class="fw-bold mb-3">THD (%)</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label>Warn</label>
                            <input type="number" step="0.01" name="thd_warn"
                                   class="form-control"
                                   value="{{ $gardu->batterySetting->thd_warn ?? '' }}">
                        </div>
                        <div class="col-md-6">
                            <label>Alarm</label>
                            <input type="number" step="0.01" name="thd_alarm"
                                   class="form-control"
                                   value="{{ $gardu->batterySetting->thd_alarm ?? '' }}">
                        </div>
                    </div>

                    {{-- SOC --}}
                    <h6 class="fw-bold mb-3">SOC (%)</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label>Warn</label>
                            <input type="number" step="0.01" name="soc_warn"
                                   class="form-control"
                                   value="{{ $gardu->batterySetting->soc_warn ?? '' }}">
                        </div>
                        <div class="col-md-6">
                            <label>Alarm</label>
                            <input type="number" step="0.01" name="soc_alarm"
                                   class="form-control"
                                   value="{{ $gardu->batterySetting->soc_alarm ?? '' }}">
                        </div>
                    </div>

                    {{-- SOH --}}
                    <h6 class="fw-bold mb-3">SOH (%)</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label>Warn</label>
                            <input type="number" step="0.01" name="soh_warn"
                                   class="form-control"
                                   value="{{ $gardu->batterySetting->soh_warn ?? '' }}">
                        </div>
                        <div class="col-md-6">
                            <label>Alarm</label>
                            <input type="number" step="0.01" name="soh_alarm"
                                   class="form-control"
                                   value="{{ $gardu->batterySetting->soh_alarm ?? '' }}">
                        </div>
                    </div>

                </div>

                {{-- FOOTER --}}
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Simpan Setting
                    </button>
                </div>

            </div>
        </form>
    </div>
</div>
