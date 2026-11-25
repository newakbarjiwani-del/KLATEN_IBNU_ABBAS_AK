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
            <a class="desk-top-title" href="{{ route($account->homeRoute()) }}">{{ $account->portalTitle() }}</a>
        </header>
        <div class="desk-scrim" id="desk-scrim" hidden></div>
        <aside class="desk-side">
            <div class="desk-brand-row">
                <a class="desk-brand" href="{{ route($account->homeRoute()) }}" title="Kembali ke beranda">
                    <div class="brand-avatar-wrap">
                        @include('partials.logo')
                    </div>
                    <div class="brand-info">
                        <span class="brand-title">
                            <strong>{{ $account->portalTitle() }}</strong>
                            <span class="brand-badge-status" title="Sistem Aktif"><span class="pulse-dot"></span> Online</span>
                        </span>
                        <small class="brand-user">{{ $account->displayName() }}</small>
                    </div>
                </a>
            </div>

            @include('partials.portal-nav')

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
