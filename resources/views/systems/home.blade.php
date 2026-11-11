@extends('layouts.portal')

@section('title', $account->systemName().' — Ibnu Abbas Klaten')

@section('content')
    <section class="frame">
        <div class="story">
            @include('partials.logo', ['class' => 'is-hero'])
            <p class="eyebrow">Sistem aktif</p>
            <h1>{{ $account->systemName() }}</h1>
            <p>Anda masuk sebagai {{ $account->displayName() }}. Modul ini masih halaman awal, siap diisi setelah login berjalan.</p>
        </div>

        <div class="panel">
            <div class="home-head">
                <div>
                    <p class="kicker">Beranda</p>
                    <h2>Selamat datang</h2>
                </div>
                <form class="logout" method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit">Keluar</button>
                </form>
            </div>

            <div class="card">
                <dl>
                    <div>
                        <dt>Nama</dt>
                        <dd>{{ $account->displayName() }}</dd>
                    </div>
                    <div>
                        <dt>Username</dt>
                        <dd>{{ $account->users }}</dd>
                    </div>
                    <div>
                        <dt>Sistem</dt>
                        <dd>{{ $account->systemName() }}</dd>
                    </div>
                </dl>
                <p>Menu di dalam sistem ini menyusul. Login sudah memisahkan Tahfidz, Kesantrian, dan Raport Akademik.</p>
            </div>
        </div>
    </section>
@endsection
