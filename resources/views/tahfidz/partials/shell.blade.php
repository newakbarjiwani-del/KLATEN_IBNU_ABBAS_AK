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
            <a class="desk-top-title" href="{{ route('tahfidz.home') }}">Tahfidz</a>
        </header>
        <div class="desk-scrim" id="desk-scrim" hidden></div>
        <aside class="desk-side">
            <div class="desk-brand-row">
                <a class="desk-brand" href="{{ route('tahfidz.home') }}" title="Kembali ke Dashboard Tahfidz">
                    <div class="brand-avatar-wrap">
                        @include('partials.logo')
                    </div>
                    <div class="brand-info">
                        <span class="brand-title">
                            <strong>Tahfidz</strong>
                            <span class="brand-badge-status" title="Sistem Aktif"><span class="pulse-dot"></span> Online</span>
                        </span>
                        <small class="brand-user">{{ $account->displayName() }}</small>
                    </div>
                </a>
            </div>

            <nav class="desk-nav" id="desk-nav" aria-label="Menu Tahfidz">
                <a
                    class="desk-link {{ request()->routeIs('tahfidz.home') ? 'is-active' : '' }}"
                    href="{{ route('tahfidz.home') }}"
                    @if (request()->routeIs('tahfidz.home')) aria-current="page" @endif
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
                            href="{{ route('tahfidz.menu', $slug) }}"
                            @if (($current['slug'] ?? null) === $slug) aria-current="page" @endif
                        >
                            <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                @if ($slug === 'murajaah')
                                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                                @elseif ($slug === 'setoran-lama')
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                @else
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                @endif
                            </svg>
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
