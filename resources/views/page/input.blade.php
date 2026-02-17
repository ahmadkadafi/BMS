@extends('layouts.master')

@section('title', 'Input Data Master')

@section('content')
<div class="page-inner">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Users</h5>
                    <button class="btn btn-primary btn-icon btn-sm" data-bs-toggle="modal" data-bs-target="#createUserModal" title="Tambah User"><i class="fas fa-plus"></i></button>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-sm align-middle">
                        <thead class="table-dark text-center"><tr><th>No</th><th>Username</th><th>Email</th><th>Role</th><th>Action</th></tr></thead>
                        <tbody>
                            @forelse ($users as $u)
                                <tr>
                                    <td class="text-center">{{ $users->firstItem() + $loop->index }}</td>
                                    <td>{{ $u->username }}</td>
                                    <td>{{ $u->email }}</td>
                                    <td class="text-center">{{ $u->role }}</td>
                                    <td>
                                        <div class="d-flex gap-1 justify-content-center">
                                            <button class="btn btn-warning btn-icon btn-sm" data-bs-toggle="modal" data-bs-target="#editUser{{ $u->id }}" title="Edit"><i class="fas fa-pen"></i></button>
                                            <form method="POST" action="{{ route('input.users.destroy', $u->id) }}" onsubmit="return confirm('Hapus data ini?')">@csrf @method('DELETE')<button class="btn btn-danger btn-icon btn-sm" title="Delete"><i class="fas fa-trash"></i></button></form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted">Tidak ada data</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    {{ $users->links() }}
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Daop</h5>
                    <button class="btn btn-primary btn-icon btn-sm" data-bs-toggle="modal" data-bs-target="#createDaopModal" title="Tambah Daop"><i class="fas fa-plus"></i></button>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-sm align-middle">
                        <thead class="table-dark text-center"><tr><th>No</th><th>Nama</th><th>Wilayah</th><th>Action</th></tr></thead>
                        <tbody>
                            @forelse ($daops as $d)
                                <tr>
                                    <td class="text-center">{{ $daops->firstItem() + $loop->index }}</td>
                                    <td>{{ $d->nama }}</td>
                                    <td>{{ $d->wilayah }}</td>
                                    <td>
                                        <div class="d-flex gap-1 justify-content-center">
                                            <button class="btn btn-warning btn-icon btn-sm" data-bs-toggle="modal" data-bs-target="#editDaop{{ $d->id }}" title="Edit"><i class="fas fa-pen"></i></button>
                                            <form method="POST" action="{{ route('input.daop.destroy', $d->id) }}" onsubmit="return confirm('Hapus data ini?')">@csrf @method('DELETE')<button class="btn btn-danger btn-icon btn-sm" title="Delete"><i class="fas fa-trash"></i></button></form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted">Tidak ada data</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    {{ $daops->links() }}
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Resor</h5>
                    <button class="btn btn-primary btn-icon btn-sm" data-bs-toggle="modal" data-bs-target="#createResorModal" title="Tambah Resor"><i class="fas fa-plus"></i></button>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-sm align-middle">
                        <thead class="table-dark text-center"><tr><th>No</th><th>Nama</th><th>Daop</th><th>n_asset</th><th>Action</th></tr></thead>
                        <tbody>
                            @forelse ($resors as $r)
                                <tr>
                                    <td class="text-center">{{ $resors->firstItem() + $loop->index }}</td>
                                    <td>{{ $r->nama }}</td>
                                    <td>{{ $r->daop?->nama ?? '-' }}</td>
                                    <td class="text-center">{{ $r->n_asset }}</td>
                                    <td><div class="d-flex gap-1 justify-content-center"><button class="btn btn-warning btn-icon btn-sm" data-bs-toggle="modal" data-bs-target="#editResor{{ $r->id }}" title="Edit"><i class="fas fa-pen"></i></button><form method="POST" action="{{ route('input.resor.destroy', $r->id) }}" onsubmit="return confirm('Hapus data ini?')">@csrf @method('DELETE')<button class="btn btn-danger btn-icon btn-sm" title="Delete"><i class="fas fa-trash"></i></button></form></div></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted">Tidak ada data</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    {{ $resors->links() }}
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Gardu</h5>
                    <button class="btn btn-primary btn-icon btn-sm" data-bs-toggle="modal" data-bs-target="#createGarduModal" title="Tambah Gardu"><i class="fas fa-plus"></i></button>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-sm align-middle">
                        <thead class="table-dark text-center"><tr><th>No</th><th>Kode</th><th>Nama</th><th>Resor</th><th>Action</th></tr></thead>
                        <tbody>
                            @forelse ($gardus as $g)
                                <tr>
                                    <td class="text-center">{{ $gardus->firstItem() + $loop->index }}</td>
                                    <td>{{ $g->kode }}</td>
                                    <td>{{ $g->nama }}</td>
                                    <td>{{ $g->resor?->nama ?? '-' }}</td>
                                    <td><div class="d-flex gap-1 justify-content-center"><button class="btn btn-warning btn-icon btn-sm" data-bs-toggle="modal" data-bs-target="#editGardu{{ $g->id }}" title="Edit"><i class="fas fa-pen"></i></button><form method="POST" action="{{ route('input.gardu.destroy', $g->id) }}" onsubmit="return confirm('Hapus data ini?')">@csrf @method('DELETE')<button class="btn btn-danger btn-icon btn-sm" title="Delete"><i class="fas fa-trash"></i></button></form></div></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted">Tidak ada data</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    {{ $gardus->links() }}
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Charger</h5>
                    <button class="btn btn-primary btn-icon btn-sm" data-bs-toggle="modal" data-bs-target="#createChargerModal" title="Tambah Charger"><i class="fas fa-plus"></i></button>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-sm align-middle">
                        <thead class="table-dark text-center"><tr><th>No</th><th>Serial No</th><th>Merk</th><th>Kapasitas</th><th>Status</th><th>Gardu</th><th>Action</th></tr></thead>
                        <tbody>
                            @forelse ($chargers as $c)
                                <tr>
                                    <td class="text-center">{{ $chargers->firstItem() + $loop->index }}</td>
                                    <td>{{ $c->serial_no }}</td>
                                    <td>{{ $c->merk }}</td>
                                    <td class="text-center">{{ $c->kapasitas }}</td>
                                    <td class="text-center">{{ $c->status }}</td>
                                    <td>{{ $c->gardu?->nama ?? '-' }}</td>
                                    <td><div class="d-flex gap-1 justify-content-center"><button class="btn btn-warning btn-icon btn-sm" data-bs-toggle="modal" data-bs-target="#editCharger{{ $c->id }}" title="Edit"><i class="fas fa-pen"></i></button><form method="POST" action="{{ route('input.charger.destroy', $c->id) }}" onsubmit="return confirm('Hapus data ini?')">@csrf @method('DELETE')<button class="btn btn-danger btn-icon btn-sm" title="Delete"><i class="fas fa-trash"></i></button></form></div></td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted">Tidak ada data</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    {{ $chargers->links() }}
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Battery</h5>
                    <button class="btn btn-primary btn-icon btn-sm" data-bs-toggle="modal" data-bs-target="#createBatteryModal" title="Tambah Battery"><i class="fas fa-plus"></i></button>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-sm align-middle">
                        <thead class="table-dark text-center"><tr><th>No</th><th>Serial No</th><th>Merk</th><th>Kapasitas</th><th>Status</th><th>Gardu</th><th>Action</th></tr></thead>
                        <tbody>
                            @forelse ($batteries as $b)
                                <tr>
                                    <td class="text-center">{{ $batteries->firstItem() + $loop->index }}</td>
                                    <td>{{ $b->serial_no }}</td>
                                    <td>{{ $b->merk }}</td>
                                    <td class="text-center">{{ $b->kapasitas }}</td>
                                    <td class="text-center">{{ $b->status }}</td>
                                    <td>{{ $b->gardu?->nama ?? '-' }}</td>
                                    <td><div class="d-flex gap-1 justify-content-center"><button class="btn btn-warning btn-icon btn-sm" data-bs-toggle="modal" data-bs-target="#editBattery{{ $b->id }}" title="Edit"><i class="fas fa-pen"></i></button><form method="POST" action="{{ route('input.battery.destroy', $b->id) }}" onsubmit="return confirm('Hapus data ini?')">@csrf @method('DELETE')<button class="btn btn-danger btn-icon btn-sm" title="Delete"><i class="fas fa-trash"></i></button></form></div></td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted">Tidak ada data</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    {{ $batteries->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@foreach($users as $u)
<div class="modal fade" id="editUser{{ $u->id }}" tabindex="-1"><div class="modal-dialog"><form method="POST" action="{{ route('input.users.update', $u->id) }}" class="modal-content">@csrf @method('PATCH')<div class="modal-header"><h5 class="modal-title">Edit User</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body row g-2"><div class="col-md-6"><label class="form-label">Username</label><input name="username" class="form-control" value="{{ $u->username }}" required></div><div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="{{ $u->email }}" required></div><div class="col-md-6"><label class="form-label">Password</label><input type="password" name="password" class="form-control" placeholder="Kosongkan jika tidak diubah"></div><div class="col-md-6"><label class="form-label">Role</label><select name="role" class="form-select" required><option value="admin" @selected($u->role === 'admin')>admin</option><option value="operator" @selected($u->role === 'operator')>operator</option><option value="viewer" @selected($u->role === 'viewer')>viewer</option></select></div></div><div class="modal-footer"><button class="btn btn-primary">Simpan</button></div></form></div></div>
@endforeach

@foreach($daops as $d)
<div class="modal fade" id="editDaop{{ $d->id }}" tabindex="-1"><div class="modal-dialog"><form method="POST" action="{{ route('input.daop.update', $d->id) }}" class="modal-content">@csrf @method('PATCH')<div class="modal-header"><h5 class="modal-title">Edit Daop</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body row g-2"><div class="col-md-6"><label class="form-label">Nama</label><input name="nama" class="form-control" value="{{ $d->nama }}" required></div><div class="col-md-6"><label class="form-label">Wilayah</label><input name="wilayah" class="form-control" value="{{ $d->wilayah }}" required></div></div><div class="modal-footer"><button class="btn btn-primary">Simpan</button></div></form></div></div>
@endforeach

@foreach($resors as $r)
<div class="modal fade" id="editResor{{ $r->id }}" tabindex="-1"><div class="modal-dialog modal-lg"><form method="POST" action="{{ route('input.resor.update', $r->id) }}" class="modal-content">@csrf @method('PATCH')<div class="modal-header"><h5 class="modal-title">Edit Resor</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body row g-2"><div class="col-md-6"><label class="form-label">Nama</label><input name="nama" class="form-control" value="{{ $r->nama }}" required></div><div class="col-md-6"><label class="form-label">Daop</label><select name="daop_id" class="form-select" required>@foreach($daopOptions as $opt)<option value="{{ $opt->id }}" @selected($r->daop_id == $opt->id)>{{ $opt->nama }}</option>@endforeach</select></div><div class="col-md-8"><label class="form-label">Alamat</label><input name="alamat" class="form-control" value="{{ $r->alamat }}" required></div><div class="col-md-4"><label class="form-label">n_asset</label><input type="number" name="n_asset" min="0" class="form-control" value="{{ $r->n_asset }}" required></div></div><div class="modal-footer"><button class="btn btn-primary">Simpan</button></div></form></div></div>
@endforeach

@foreach($gardus as $g)
<div class="modal fade" id="editGardu{{ $g->id }}" tabindex="-1"><div class="modal-dialog modal-lg"><form method="POST" action="{{ route('input.gardu.update', $g->id) }}" class="modal-content">@csrf @method('PATCH')<div class="modal-header"><h5 class="modal-title">Edit Gardu</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body row g-2"><div class="col-md-6"><label class="form-label">Kode</label><input name="kode" class="form-control" value="{{ $g->kode }}" required></div><div class="col-md-6"><label class="form-label">Nama</label><input name="nama" class="form-control" value="{{ $g->nama }}" required></div><div class="col-md-3"><label class="form-label">n_bank</label><input type="number" name="n_bank" min="0" class="form-control" value="{{ $g->n_bank }}" required></div><div class="col-md-3"><label class="form-label">n_batt</label><input type="number" name="n_batt" min="0" class="form-control" value="{{ $g->n_batt }}" required></div><div class="col-md-6"><label class="form-label">Resor</label><select name="resor_id" class="form-select" required>@foreach($resorOptions as $opt)<option value="{{ $opt->id }}" @selected($g->resor_id == $opt->id)>{{ $opt->nama }}</option>@endforeach</select></div><div class="col-md-6"><label class="form-label">Latitude</label><input type="number" step="0.0000001" name="latitude" class="form-control" value="{{ $g->latitude }}" required></div><div class="col-md-6"><label class="form-label">Longitude</label><input type="number" step="0.0000001" name="longitude" class="form-control" value="{{ $g->longitude }}" required></div><div class="col-md-12"><label class="form-label">Address</label><input name="address" class="form-control" value="{{ $g->address }}" required></div></div><div class="modal-footer"><button class="btn btn-primary">Simpan</button></div></form></div></div>
@endforeach
@foreach($chargers as $c)
<div class="modal fade" id="editCharger{{ $c->id }}" tabindex="-1"><div class="modal-dialog modal-lg"><form method="POST" action="{{ route('input.charger.update', $c->id) }}" class="modal-content">@csrf @method('PATCH')<div class="modal-header"><h5 class="modal-title">Edit Charger</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body row g-2"><div class="col-md-6"><label class="form-label">Serial No</label><input name="serial_no" class="form-control" value="{{ $c->serial_no }}" required></div><div class="col-md-6"><label class="form-label">Merk</label><input name="merk" class="form-control" value="{{ $c->merk }}" required></div><div class="col-md-4"><label class="form-label">Kapasitas</label><input type="number" name="kapasitas" min="0" class="form-control" value="{{ $c->kapasitas }}" required></div><div class="col-md-4"><label class="form-label">Pemasangan</label><input type="date" name="pemasangan" class="form-control" value="{{ $c->pemasangan }}" required></div><div class="col-md-4"><label class="form-label">Status</label><select name="status" class="form-select" required><option value="active" @selected($c->status === 'active')>active</option><option value="fault" @selected($c->status === 'fault')>fault</option><option value="offline" @selected($c->status === 'offline')>offline</option></select></div><div class="col-md-12"><label class="form-label">Gardu</label><select name="gardu_id" class="form-select" required>@foreach($garduOptions as $opt)<option value="{{ $opt->id }}" @selected($c->gardu_id == $opt->id)>{{ $opt->nama }}</option>@endforeach</select></div></div><div class="modal-footer"><button class="btn btn-primary">Simpan</button></div></form></div></div>
@endforeach

@foreach($batteries as $b)
<div class="modal fade" id="editBattery{{ $b->id }}" tabindex="-1"><div class="modal-dialog modal-lg"><form method="POST" action="{{ route('input.battery.update', $b->id) }}" class="modal-content">@csrf @method('PATCH')<div class="modal-header"><h5 class="modal-title">Edit Battery</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body row g-2"><div class="col-md-6"><label class="form-label">Serial No</label><input name="serial_no" class="form-control" value="{{ $b->serial_no }}" required></div><div class="col-md-6"><label class="form-label">Merk</label><input name="merk" class="form-control" value="{{ $b->merk }}" required></div><div class="col-md-4"><label class="form-label">Kapasitas</label><input type="number" name="kapasitas" min="0" class="form-control" value="{{ $b->kapasitas }}" required></div><div class="col-md-4"><label class="form-label">Pemasangan</label><input type="date" name="pemasangan" class="form-control" value="{{ $b->pemasangan }}" required></div><div class="col-md-4"><label class="form-label">Status</label><select name="status" class="form-select" required><option value="active" @selected($b->status === 'active')>active</option><option value="maintenance" @selected($b->status === 'maintenance')>maintenance</option><option value="retired" @selected($b->status === 'retired')>retired</option></select></div><div class="col-md-12"><label class="form-label">Gardu</label><select name="gardu_id" class="form-select" required>@foreach($garduOptions as $opt)<option value="{{ $opt->id }}" @selected($b->gardu_id == $opt->id)>{{ $opt->nama }}</option>@endforeach</select></div></div><div class="modal-footer"><button class="btn btn-primary">Simpan</button></div></form></div></div>
@endforeach

<div class="modal fade" id="createUserModal" tabindex="-1"><div class="modal-dialog"><form method="POST" action="{{ route('input.users.store') }}" class="modal-content">@csrf<div class="modal-header"><h5 class="modal-title">Tambah User</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body row g-2"><div class="col-md-6"><label class="form-label">Username</label><input name="username" class="form-control" required></div><div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div><div class="col-md-6"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div><div class="col-md-6"><label class="form-label">Role</label><select name="role" class="form-select" required><option value="admin">admin</option><option value="operator" selected>operator</option><option value="viewer">viewer</option></select></div></div><div class="modal-footer"><button class="btn btn-primary">Simpan</button></div></form></div></div>

<div class="modal fade" id="createDaopModal" tabindex="-1"><div class="modal-dialog"><form method="POST" action="{{ route('input.daop.store') }}" class="modal-content">@csrf<div class="modal-header"><h5 class="modal-title">Tambah Daop</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body row g-2"><div class="col-md-6"><label class="form-label">Nama</label><input name="nama" class="form-control" required></div><div class="col-md-6"><label class="form-label">Wilayah</label><input name="wilayah" class="form-control" required></div></div><div class="modal-footer"><button class="btn btn-primary">Simpan</button></div></form></div></div>

<div class="modal fade" id="createResorModal" tabindex="-1"><div class="modal-dialog modal-lg"><form method="POST" action="{{ route('input.resor.store') }}" class="modal-content">@csrf<div class="modal-header"><h5 class="modal-title">Tambah Resor</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body row g-2"><div class="col-md-6"><label class="form-label">Nama</label><input name="nama" class="form-control" required></div><div class="col-md-6"><label class="form-label">Daop</label><select name="daop_id" class="form-select" required>@foreach($daopOptions as $opt)<option value="{{ $opt->id }}">{{ $opt->nama }}</option>@endforeach</select></div><div class="col-md-8"><label class="form-label">Alamat</label><input name="alamat" class="form-control" required></div><div class="col-md-4"><label class="form-label">n_asset</label><input type="number" name="n_asset" min="0" class="form-control" value="0" required></div></div><div class="modal-footer"><button class="btn btn-primary">Simpan</button></div></form></div></div>

<div class="modal fade" id="createGarduModal" tabindex="-1"><div class="modal-dialog modal-lg"><form method="POST" action="{{ route('input.gardu.store') }}" class="modal-content">@csrf<div class="modal-header"><h5 class="modal-title">Tambah Gardu</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body row g-2"><div class="col-md-6"><label class="form-label">Kode</label><input name="kode" class="form-control" required></div><div class="col-md-6"><label class="form-label">Nama</label><input name="nama" class="form-control" required></div><div class="col-md-3"><label class="form-label">n_bank</label><input type="number" name="n_bank" min="0" class="form-control" value="0" required></div><div class="col-md-3"><label class="form-label">n_batt</label><input type="number" name="n_batt" min="0" class="form-control" value="0" required></div><div class="col-md-6"><label class="form-label">Resor</label><select name="resor_id" class="form-select" required>@foreach($resorOptions as $opt)<option value="{{ $opt->id }}">{{ $opt->nama }}</option>@endforeach</select></div><div class="col-md-6"><label class="form-label">Latitude</label><input type="number" step="0.0000001" name="latitude" class="form-control" required></div><div class="col-md-6"><label class="form-label">Longitude</label><input type="number" step="0.0000001" name="longitude" class="form-control" required></div><div class="col-md-12"><label class="form-label">Address</label><input name="address" class="form-control" required></div></div><div class="modal-footer"><button class="btn btn-primary">Simpan</button></div></form></div></div>

<div class="modal fade" id="createChargerModal" tabindex="-1"><div class="modal-dialog modal-lg"><form method="POST" action="{{ route('input.charger.store') }}" class="modal-content">@csrf<div class="modal-header"><h5 class="modal-title">Tambah Charger</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body row g-2"><div class="col-md-6"><label class="form-label">Serial No</label><input name="serial_no" class="form-control" required></div><div class="col-md-6"><label class="form-label">Merk</label><input name="merk" class="form-control" required></div><div class="col-md-4"><label class="form-label">Kapasitas</label><input type="number" name="kapasitas" min="0" class="form-control" value="0" required></div><div class="col-md-4"><label class="form-label">Pemasangan</label><input type="date" name="pemasangan" class="form-control" required></div><div class="col-md-4"><label class="form-label">Status</label><select name="status" class="form-select" required><option value="active" selected>active</option><option value="fault">fault</option><option value="offline">offline</option></select></div><div class="col-md-12"><label class="form-label">Gardu</label><select name="gardu_id" class="form-select" required>@foreach($garduOptions as $opt)<option value="{{ $opt->id }}">{{ $opt->nama }}</option>@endforeach</select></div></div><div class="modal-footer"><button class="btn btn-primary">Simpan</button></div></form></div></div>

<div class="modal fade" id="createBatteryModal" tabindex="-1"><div class="modal-dialog modal-lg"><form method="POST" action="{{ route('input.battery.store') }}" class="modal-content">@csrf<div class="modal-header"><h5 class="modal-title">Tambah Battery</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body row g-2"><div class="col-md-6"><label class="form-label">Serial No</label><input name="serial_no" class="form-control" required></div><div class="col-md-6"><label class="form-label">Merk</label><input name="merk" class="form-control" required></div><div class="col-md-4"><label class="form-label">Kapasitas</label><input type="number" name="kapasitas" min="0" class="form-control" value="0" required></div><div class="col-md-4"><label class="form-label">Pemasangan</label><input type="date" name="pemasangan" class="form-control" required></div><div class="col-md-4"><label class="form-label">Status</label><select name="status" class="form-select" required><option value="active" selected>active</option><option value="maintenance">maintenance</option><option value="retired">retired</option></select></div><div class="col-md-12"><label class="form-label">Gardu</label><select name="gardu_id" class="form-select" required>@foreach($garduOptions as $opt)<option value="{{ $opt->id }}">{{ $opt->nama }}</option>@endforeach</select></div></div><div class="modal-footer"><button class="btn btn-primary">Simpan</button></div></form></div></div>
@endsection
