@extends('layouts.portal')

@section('title', 'Masuk — Ibnu Abbas Klaten')

@section('content')
    <section class="frame">
        <div class="story">
            @include('partials.logo', ['class' => 'is-hero'])
            <p class="eyebrow">PPTQ Ibnu Abbas</p>
            <h1>Klaten</h1>
            <p>Masuk dengan username unit. Menu yang tampil mengikuti hak akses akun.</p>
            <ul class="systems">
                <li><span>01</span> Tahfidz</li>
                <li><span>02</span> Kesantrian</li>
                <li><span>03</span> Raport Akademik</li>
            </ul>
        </div>

        <div class="panel">
            <h2>Masuk</h2>
            <p class="intro">Gunakan username unit. Aplikasi di dalam mengikuti hak akses akun tersebut.</p>

            <form method="POST" action="{{ route('login.store') }}">
                @csrf
                <label>
                    Username
                    <input
                        type="text"
                        name="users"
                        value="{{ old('users') }}"
                        maxlength="15"
                        placeholder="Ketik username akun..."
                        autocomplete="username"
                        required
                        autofocus
                    >
                </label>
                <label>
                    Password
                    <input
                        type="password"
                        name="password"
                        placeholder="••••••••"
                        autocomplete="current-password"
                        required
                    >
                </label>
                <button type="submit" class="btn-primary" style="margin-top: 8px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 6px;">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                        <polyline points="10 17 15 12 10 7"></polyline>
                        <line x1="15" y1="12" x2="3" y2="12"></line>
                    </svg>
                    Masuk ke Sistem
                </button>
            </form>
            <p class="note">Satu akun dipakai bersama. Menu yang terbuka sesuai hak akses unit.</p>
        </div>
    </section>
@endsection
