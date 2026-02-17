<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\ResorUserResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function login(Request $request): View|RedirectResponse
    {
        if ($request->session()->has('auth_user')) {
            return redirect()->route('dashboard');
        }

        return view('page.login');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'max:100'],
        ]);

        $user = User::query()
            ->where('username', $credentials['username'])
            ->first();

        if (! $user || ! $this->passwordMatches($credentials['password'], $user->password)) {
            return back()->withInput($request->only('username'))->withErrors([
                'username' => 'Username atau password tidak sesuai.',
            ]);
        }

        $authUser = [
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role,
        ];

        if (($user->role ?? null) !== 'admin') {
            $allowedResorId = ResorUserResolver::resolveAllowedResorId($user->username);

            if (ResorUserResolver::isScopedUsername($user->username) && ! $allowedResorId) {
                return back()->withInput($request->only('username'))->withErrors([
                    'username' => 'Resor untuk akun ini belum tersedia di database.',
                ]);
            }

            if ($allowedResorId) {
                $authUser['allowed_resor_id'] = $allowedResorId;
            }
        }

        $request->session()->regenerate();
        $request->session()->put('auth_user', $authUser);

        if (isset($authUser['allowed_resor_id'])) {
            return redirect()->route('monitoring.resor', ['resor' => $authUser['allowed_resor_id']]);
        }

        return redirect()->route('dashboard')->with('status', 'Login berhasil. Selamat datang di Dashboard Monitoring Battery.');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Anda sudah logout.');
    }

    private function passwordMatches(string $plainPassword, string $storedPassword): bool
    {
        if ($plainPassword === $storedPassword) {
            return true;
        }

        return Hash::check($plainPassword, $storedPassword);
    }
}
