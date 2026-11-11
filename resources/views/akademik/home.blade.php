@extends('akademik.partials.shell')

@section('title', 'Dashboard Raport Akademik — Ibnu Abbas Klaten')

@section('desk')
    @include('kesantrian.partials.page-head', [
        'kicker' => 'PPTQ Ibnu Abbas Klaten',
        'heading' => 'Dashboard Raport Akademik',
        'summary' => $account->displayName().'. Unggah raport atau buka arsip yang sudah masuk.',
    ])

    <div class="dashboard-hero-banner">
        <div class="hero-banner-content">
            <div class="hero-banner-tag">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                </svg>
                Portal Administrasi Akademik
            </div>
            <h2>Ahlan wa Sahlan, {{ $account->displayName() }}</h2>
            <p>Kelola berkas PDF raport akademik santri PPTQ Ibnu Abbas Klaten menurut tahun ajaran, kelas, dan jenis ujian.</p>
        </div>
        <div class="hero-banner-quick">
            <a href="{{ route('akademik.menu', 'upload-raport') }}" class="hero-quick-btn is-primary">Upload Raport</a>
            <a href="{{ route('akademik.menu', 'data-raport') }}" class="hero-quick-btn">Data Raport</a>
        </div>
    </div>

    <div class="desk-board">
        @foreach ($groups as $group => $items)
            <section class="board-card">
                <div class="board-card-head">
                    <h2>{{ $group }}</h2>
                    <span class="board-card-count">{{ count($items) }} Menu</span>
                </div>
                <ul>
                    @foreach ($items as $item)
                        <li>
                            <a href="{{ route('akademik.menu', $item['slug']) }}" class="board-item-link">
                                <div class="board-item-content">
                                    <strong>{{ $item['label'] }}</strong>
                                    <small>{{ $item['summary'] }}</small>
                                </div>
                                <span class="board-item-arrow" aria-hidden="true">&rarr;</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach
    </div>
@endsection
