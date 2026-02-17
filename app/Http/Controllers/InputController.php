<?php

namespace App\Http\Controllers;

use App\Models\Battery;
use App\Models\Charger;
use App\Models\Daop;
use App\Models\Gardu;
use App\Models\Resor;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class InputController extends Controller
{
    public function index(): View
    {
        return view('page.input', [
            'users' => User::query()->orderByDesc('id')->paginate(8, ['*'], 'users_page')->withQueryString(),
            'daops' => Daop::query()->orderByDesc('id')->paginate(8, ['*'], 'daops_page')->withQueryString(),
            'resors' => Resor::query()->with('daop')->orderByDesc('id')->paginate(8, ['*'], 'resors_page')->withQueryString(),
            'gardus' => Gardu::query()->with('resor')->orderByDesc('id')->paginate(8, ['*'], 'gardus_page')->withQueryString(),
            'chargers' => Charger::query()->with('gardu')->orderByDesc('id')->paginate(8, ['*'], 'chargers_page')->withQueryString(),
            'batteries' => Battery::query()->with('gardu')->orderByDesc('id')->paginate(8, ['*'], 'batteries_page')->withQueryString(),
            'daopOptions' => Daop::query()->orderBy('nama')->get(['id', 'nama']),
            'resorOptions' => Resor::query()->orderBy('nama')->get(['id', 'nama']),
            'garduOptions' => Gardu::query()->orderBy('nama')->get(['id', 'nama']),
        ]);
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'max:100'],
            'role' => ['required', 'in:admin,operator,viewer'],
        ]);

        User::create([
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
        ]);

        return back()->with('success', 'Data user berhasil ditambahkan.');
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6', 'max:100'],
            'role' => ['required', 'in:admin,operator,viewer'],
        ]);

        $payload = [
            'username' => $data['username'],
            'email' => $data['email'],
            'role' => $data['role'],
        ];

        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        $user->update($payload);

        return back()->with('success', 'Data user berhasil diperbarui.');
    }

    public function destroyUser(User $user): RedirectResponse
    {
        return $this->destroyWithGuard(fn () => $user->delete(), 'Data user berhasil dihapus.');
    }

    public function storeDaop(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:50'],
            'wilayah' => ['required', 'string', 'max:50'],
        ]);

        Daop::create($data);

        return back()->with('success', 'Data daop berhasil ditambahkan.');
    }

    public function updateDaop(Request $request, Daop $daop): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:50'],
            'wilayah' => ['required', 'string', 'max:50'],
        ]);

        $daop->update($data);

        return back()->with('success', 'Data daop berhasil diperbarui.');
    }

    public function destroyDaop(Daop $daop): RedirectResponse
    {
        return $this->destroyWithGuard(fn () => $daop->delete(), 'Data daop berhasil dihapus.');
    }

    public function storeResor(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'alamat' => ['required', 'string', 'max:300'],
            'n_asset' => ['required', 'integer', 'min:0'],
            'daop_id' => ['required', 'exists:daop,id'],
        ]);

        Resor::create($data);

        return back()->with('success', 'Data resor berhasil ditambahkan.');
    }

    public function updateResor(Request $request, Resor $resor): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'alamat' => ['required', 'string', 'max:300'],
            'n_asset' => ['required', 'integer', 'min:0'],
            'daop_id' => ['required', 'exists:daop,id'],
        ]);

        $resor->update($data);

        return back()->with('success', 'Data resor berhasil diperbarui.');
    }

    public function destroyResor(Resor $resor): RedirectResponse
    {
        return $this->destroyWithGuard(fn () => $resor->delete(), 'Data resor berhasil dihapus.');
    }

    public function storeGardu(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kode' => ['required', 'string', 'max:100'],
            'nama' => ['required', 'string', 'max:100'],
            'n_bank' => ['required', 'integer', 'min:0'],
            'n_batt' => ['required', 'integer', 'min:0'],
            'address' => ['required', 'string'],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'resor_id' => ['required', 'exists:resor,id'],
        ]);

        Gardu::create($data);

        return back()->with('success', 'Data gardu berhasil ditambahkan.');
    }

    public function updateGardu(Request $request, Gardu $gardu): RedirectResponse
    {
        $data = $request->validate([
            'kode' => ['required', 'string', 'max:100'],
            'nama' => ['required', 'string', 'max:100'],
            'n_bank' => ['required', 'integer', 'min:0'],
            'n_batt' => ['required', 'integer', 'min:0'],
            'address' => ['required', 'string'],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'resor_id' => ['required', 'exists:resor,id'],
        ]);

        $gardu->update($data);

        return back()->with('success', 'Data gardu berhasil diperbarui.');
    }

    public function destroyGardu(Gardu $gardu): RedirectResponse
    {
        return $this->destroyWithGuard(fn () => $gardu->delete(), 'Data gardu berhasil dihapus.');
    }

    public function storeCharger(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'serial_no' => ['required', 'string', 'max:50'],
            'merk' => ['required', 'string', 'max:50'],
            'kapasitas' => ['required', 'integer', 'min:0'],
            'pemasangan' => ['required', 'date'],
            'status' => ['required', 'in:active,fault,offline'],
            'gardu_id' => ['required', 'exists:gardu,id'],
        ]);

        Charger::create($data);

        return back()->with('success', 'Data charger berhasil ditambahkan.');
    }

    public function updateCharger(Request $request, Charger $charger): RedirectResponse
    {
        $data = $request->validate([
            'serial_no' => ['required', 'string', 'max:50'],
            'merk' => ['required', 'string', 'max:50'],
            'kapasitas' => ['required', 'integer', 'min:0'],
            'pemasangan' => ['required', 'date'],
            'status' => ['required', 'in:active,fault,offline'],
            'gardu_id' => ['required', 'exists:gardu,id'],
        ]);

        $charger->update($data);

        return back()->with('success', 'Data charger berhasil diperbarui.');
    }

    public function destroyCharger(Charger $charger): RedirectResponse
    {
        return $this->destroyWithGuard(fn () => $charger->delete(), 'Data charger berhasil dihapus.');
    }

    public function storeBattery(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'serial_no' => ['required', 'string', 'max:50'],
            'merk' => ['required', 'string', 'max:50'],
            'kapasitas' => ['required', 'integer', 'min:0'],
            'pemasangan' => ['required', 'date'],
            'status' => ['required', 'in:active,maintenance,retired'],
            'gardu_id' => ['required', 'exists:gardu,id'],
        ]);

        Battery::create($data);

        return back()->with('success', 'Data battery berhasil ditambahkan.');
    }

    public function updateBattery(Request $request, Battery $battery): RedirectResponse
    {
        $data = $request->validate([
            'serial_no' => ['required', 'string', 'max:50'],
            'merk' => ['required', 'string', 'max:50'],
            'kapasitas' => ['required', 'integer', 'min:0'],
            'pemasangan' => ['required', 'date'],
            'status' => ['required', 'in:active,maintenance,retired'],
            'gardu_id' => ['required', 'exists:gardu,id'],
        ]);

        $battery->update($data);

        return back()->with('success', 'Data battery berhasil diperbarui.');
    }

    public function destroyBattery(Battery $battery): RedirectResponse
    {
        return $this->destroyWithGuard(fn () => $battery->delete(), 'Data battery berhasil dihapus.');
    }

    private function destroyWithGuard(callable $callback, string $successMessage): RedirectResponse
    {
        try {
            $callback();

            return back()->with('success', $successMessage);
        } catch (Throwable $e) {
            return back()->withErrors('Data tidak bisa dihapus karena masih dipakai relasi tabel lain.');
        }
    }
}
