@extends('layouts.auth')

@section('title', 'Login | Dashboard Monitoring Battery')

@section('page_css')
<link rel="stylesheet" href="{{ asset('templating/assets/css/login-page.css') }}" />
@endsection

@section('content')
<div class="auth-wrapper">
    <div class="login-shell">
        <div class="login-visual" aria-hidden="true"></div>

        <div class="login-body">
            <div class="login-brand">
                <img src="{{ asset('templating/assets/img/kaiadmin/logo_bms_black.png') }}" alt="Logo KAI">
            </div>

            <div class="system-copy">
                <p>Sistem Battery Management System adalah sistem pemantauan kondisi baterai gardu traksi secara real-time untuk mendukung keandalan operasional.</p>
            </div>

            @if (session('status'))
                <div class="alert alert-info py-2" role="alert">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger py-2" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('login.authenticate') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input
                        type="text"
                        class="form-control @error('username') is-invalid @enderror"
                        id="username"
                        name="username"
                        value="{{ old('username') }}"
                        placeholder="Masukkan username"
                        required
                        autofocus
                    />
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input
                        type="password"
                        class="form-control @error('password') is-invalid @enderror"
                        id="password"
                        name="password"
                        placeholder="Masukkan password"
                        required
                    />
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary btn-login">Login</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection