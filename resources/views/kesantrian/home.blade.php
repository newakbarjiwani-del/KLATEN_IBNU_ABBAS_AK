@extends('kesantrian.partials.shell')

@section('title', 'Dashboard Kesantrian — Ibnu Abbas Klaten')

@section('desk')
    @include('kesantrian.partials.page-head', [
        'kicker' => 'PPTQ Ibnu Abbas Klaten',
        'heading' => 'Dashboard Kesantrian',
        'summary' => $account->displayName().'. Pilih menu di samping, atau mulai dari kelompok kerja di bawah.',
    ])

    <div class="dashboard-hero-banner">
        <div class="hero-banner-content">
            <div class="hero-banner-tag">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                </svg>
                Portal Administrasi Kesantrian
            </div>
            <h2>Ahlan wa Sahlan, {{ $account->displayName() }}</h2>
            <p>Kelola data akademik kesantrian, catatan pelanggaran santri, serta input dan arsip raport sikap dan pengembangan diri santri PPTQ Ibnu Abbas Klaten dengan rapi dan terintegrasi.</p>
        </div>
        <div class="hero-banner-quick">
            <a href="{{ route('kesantrian.menu', 'data-raport-kesantrian') }}" class="hero-quick-btn is-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                </svg>
                Lihat Raport Kesantrian
            </a>
            <a href="{{ route('kesantrian.menu', 'input-raport-kesantrian') }}" class="hero-quick-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="17 8 12 3 7 8"></polyline>
                    <line x1="12" y1="3" x2="12" y2="15"></line>
                </svg>
                Import Nilai Raport
            </a>
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
                            <a href="{{ route('kesantrian.menu', $item['slug']) }}" class="board-item-link">
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
