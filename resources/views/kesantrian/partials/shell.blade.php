@extends('layouts.portal')

@section('dashboard')
    <div class="desk">
        <header class="desk-top">
            <button type="button" class="mobile-nav-toggle" id="mobile-nav-toggle" aria-expanded="false" aria-controls="desk-nav" aria-label="Buka navigasi menu">
                <svg class="icon-menu" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
                <svg class="icon-close" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
            <a class="desk-top-title" href="{{ route('kesantrian.home') }}">Kesantrian</a>
        </header>
        <div class="desk-scrim" id="desk-scrim" hidden></div>
        <aside class="desk-side">
            <div class="desk-brand-row">
                <a class="desk-brand" href="{{ route('kesantrian.home') }}" title="Kembali ke Dashboard Kesantrian">
                    <div class="brand-avatar-wrap">
                        @include('partials.logo')
                    </div>
                    <div class="brand-info">
                        <span class="brand-title">
                            <strong>Kesantrian</strong>
                            <span class="brand-badge-status" title="Sistem Aktif"><span class="pulse-dot"></span> Online</span>
                        </span>
                        <small class="brand-user">{{ $account->displayName() }}</small>
                    </div>
                </a>
            </div>

            <nav class="desk-nav" id="desk-nav" aria-label="Menu Kesantrian">
                <a
                    class="desk-link {{ request()->routeIs('kesantrian.home') ? 'is-active' : '' }}"
                    href="{{ route('kesantrian.home') }}"
                    @if (request()->routeIs('kesantrian.home')) aria-current="page" @endif
                >
                    <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                    <span>Dashboard Utama</span>
                </a>

                @foreach ($groups as $group => $items)
                    <p class="desk-group">{{ $group }}</p>
                    @foreach ($items as $item)
                        @php $slug = $item['slug']; @endphp
                        <a
                            class="desk-link {{ ($current['slug'] ?? null) === $slug ? 'is-active' : '' }}"
                            href="{{ route('kesantrian.menu', $slug) }}"
                            @if (($current['slug'] ?? null) === $slug) aria-current="page" @endif
                        >
                            @if ($slug === 'tahun-akademik')
                                <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                            @elseif ($slug === 'master-pelanggaran')
                                <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                            @elseif ($slug === 'input-pelanggaran')
                                <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                            @elseif ($slug === 'data-pelanggaran')
                                <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
                            @elseif ($slug === 'input-raport-kesantrian')
                                <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                            @elseif ($slug === 'data-raport-kesantrian')
                                <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                            @elseif ($slug === 'upload-raport')
                                <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 16 12 12 8 16"></polyline><line x1="12" y1="12" x2="12" y2="21"></line><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"></path></svg>
                            @elseif ($slug === 'data-raport')
                                <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                            @else
                                <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle></svg>
                            @endif
                            <span>{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                @endforeach
            </nav>

            <div class="desk-foot">
                @include('partials.theme-button')
                <form class="logout desk-logout" method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" title="Keluar dari akun">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                        <span>Keluar</span>
                    </button>
                </form>
            </div>
        </aside>

        <section class="desk-main">
            @yield('desk')
        </section>
    </div>
    @include('kesantrian.partials.nis-suggest')
    <script>
        (function () {
            var toggle = document.getElementById('mobile-nav-toggle');
            var side = document.querySelector('.desk-side');
            var scrim = document.getElementById('desk-scrim');
            if (!toggle || !side || !scrim) {
                return;
            }

            var setOpen = function (open) {
                side.classList.toggle('is-mobile-open', open);
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                toggle.setAttribute('aria-label', open ? 'Tutup navigasi menu' : 'Buka navigasi menu');
                scrim.hidden = !open;
                document.body.classList.toggle('nav-open', open);
            };

            toggle.addEventListener('click', function () {
                setOpen(!side.classList.contains('is-mobile-open'));
            });

            scrim.addEventListener('click', function () {
                setOpen(false);
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    setOpen(false);
                }
            });

            side.querySelectorAll('a').forEach(function (link) {
                link.addEventListener('click', function () {
                    if (window.matchMedia('(max-width: 980px)').matches) {
                        setOpen(false);
                    }
                });
            });
        })();
    </script>
@endsection
