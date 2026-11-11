@extends('layouts.portal')

@section('title', 'Masuk — Ibnu Abbas Klaten')

@section('content')
    <section class="frame">
        <div class="story">
            @include('partials.logo', ['class' => 'is-hero'])
            <p class="eyebrow">PPTQ Ibnu Abbas</p>
            <h1>Klaten</h1>
            <p>Pilih aplikasi lebih dulu, lalu masuk dengan username dan password akun yang sesuai.</p>
            <ul class="systems">
                <li><span>01</span> Tahfidz</li>
                <li><span>02</span> Kesantrian</li>
                <li><span>03</span> Raport Akademik</li>
            </ul>
        </div>

        <div class="panel">
            <h2>Masuk</h2>
            <p class="intro">Pilih aplikasi di atas username, lalu masuk dengan akun sistem tersebut.</p>

            <form method="POST" action="{{ route('login.store') }}">
                @csrf
                <label>
                    Aplikasi
                    <select name="aplikasi" required autofocus>
                        <option value="" @selected(old('aplikasi') === null || old('aplikasi') === '')>Pilih aplikasi sistem</option>
                        @foreach (\App\Models\CyberKey::SYSTEMS as $code => $system)
                            <option value="{{ $code }}" @selected(old('aplikasi') === $code)>{{ $system['name'] }}</option>
                        @endforeach
                    </select>
                </label>
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
            <p class="note">Aplikasi harus sesuai dengan kelompok akun.</p>
        </div>
    </section>
@endsection
