<div class="modal fade" id="edit{{ $charger->id }}" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('chargersetting.store') }}">
            @csrf
            <input type="hidden" name="charger_id" value="{{ $charger->id }}">

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Charger Setting - {{ $charger->serial_no }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Float (V)</label>
                            <input type="number" step="0.01" name="float" class="form-control" value="{{ $s->float ?? '' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Boost (V)</label>
                            <input type="number" step="0.01" name="boost" class="form-control" value="{{ $s->boost ?? '' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Warn Min Volt (V)</label>
                            <input type="number" step="0.01" name="warn_min_volt" class="form-control" value="{{ $s->warn_min_volt ?? '' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Alarm Min Volt (V)</label>
                            <input type="number" step="0.01" name="alarm_min_volt" class="form-control" value="{{ $s->alarm_min_volt ?? '' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Warn Max Volt (V)</label>
                            <input type="number" step="0.01" name="warn_max_volt" class="form-control" value="{{ $s->warn_max_volt ?? '' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Alarm Max Volt (V)</label>
                            <input type="number" step="0.01" name="alarm_max_volt" class="form-control" value="{{ $s->alarm_max_volt ?? '' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Warn Curr (A)</label>
                            <input type="number" step="0.01" name="warn_curr" class="form-control" value="{{ $s->warn_curr ?? '' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Alarm Curr (A)</label>
                            <input type="number" step="0.01" name="alarm_curr" class="form-control" value="{{ $s->alarm_curr ?? '' }}">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-primary">Simpan Setting</button>
                </div>
            </div>
        </form>
    </div>
</div>

@if ($s)
    <div class="modal fade" id="delete{{ $charger->id }}" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('chargersetting.destroy', $charger->id) }}">
                @csrf
                @method('DELETE')
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-danger">Hapus Charger Setting</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        Yakin hapus setting untuk charger <strong>{{ $charger->serial_no }}</strong>?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button class="btn btn-danger">Hapus</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endif
