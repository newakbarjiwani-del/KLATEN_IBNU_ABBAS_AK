@php
    $notices = collect($errors->all())
        ->map(fn ($text) => ['text' => $text, 'type' => 'error'])
        ->when(session('status'), fn ($items) => $items->push(['text' => session('status'), 'type' => 'success']))
        ->values();
    $pageTitle = trim($__env->yieldContent('title', 'Ibnu Abbas Klaten'));
    $pageDescription = 'Portal masuk Back Office PPTQ Ibnu Abbas Klaten untuk Tahfidz, Kesantrian, dan Raport Akademik.';
    $at = '@';
    $loginSchema = json_encode([
        $at.'context' => 'https://schema.org',
        $at.'type' => 'EducationalOrganization',
        'name' => 'PPTQ Ibnu Abbas Klaten',
        'url' => route('login'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#102433">
    <title>{{ $pageTitle }}</title>
    @guest
        <meta name="description" content="{{ $pageDescription }}">
        <meta name="robots" content="index, follow">
        <link rel="canonical" href="{{ route('login') }}">
        <meta property="og:locale" content="id_ID">
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="PPTQ Ibnu Abbas Klaten">
        <meta property="og:title" content="{{ $pageTitle }}">
        <meta property="og:description" content="{{ $pageDescription }}">
        <meta property="og:url" content="{{ route('login') }}">
        <meta property="og:image" content="{{ asset('logo.png') }}">
        <script type="application/ld+json">{!! $loginSchema !!}</script>
    @else
        <meta name="robots" content="noindex, nofollow">
    @endguest
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link href="https://fonts.bunny.net/css?family=ibm-plex-sans:400,500,600,700&display=swap" rel="stylesheet">
    @if ($notices->isNotEmpty())
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js@1.12.0/src/toastify.min.css">
    @endif
    <link rel="icon" href="{{ asset('logo.png') }}" type="image/png">
    <link rel="stylesheet" href="{{ asset('css/portal.css') }}?v=43">
    <script>
        (function () {
            var saved = localStorage.getItem('theme');
            var theme = saved === 'light' || saved === 'dark'
                ? saved
                : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
</head>
<body>
    @hasSection('dashboard')
        @yield('dashboard')
    @else
        @include('partials.theme-button')
        <main class="portal">
            @yield('content')
        </main>
    @endif
    <div id="page-loader" class="top-progress" aria-hidden="true"></div>
    <script>
        (function () {
            var toggle = document.getElementById('theme-toggle');
            if (!toggle) {
                return;
            }

            var apply = function (theme) {
                document.documentElement.setAttribute('data-theme', theme);
                localStorage.setItem('theme', theme);
                var dark = theme === 'dark';
                toggle.setAttribute('aria-label', dark ? 'Mode terang' : 'Mode gelap');
                toggle.setAttribute('aria-pressed', dark ? 'true' : 'false');
            };

            apply(document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light');

            toggle.addEventListener('click', function () {
                apply(document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
            });
        })();
    </script>
    @if ($notices->isNotEmpty())
        <script src="https://cdn.jsdelivr.net/npm/toastify-js@1.12.0/src/toastify.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const notices = @json($notices);

            notices.forEach((notice, index) => {
                window.setTimeout(() => {
                    const success = notice.type === 'success';

                    Toastify({
                        text: notice.text,
                        duration: 4500,
                        close: true,
                        gravity: 'top',
                        position: 'right',
                        stopOnFocus: true,
                        className: success ? 'portal-toast is-success' : 'portal-toast is-error',
                        offset: { x: 16, y: 16 },
                        style: {
                            background: success ? '#0875ab' : (document.documentElement.getAttribute('data-theme') === 'dark' ? '#3d1d1d' : '#fef2f2'),
                            color: success ? '#ffffff' : (document.documentElement.getAttribute('data-theme') === 'dark' ? '#fca5a5' : '#991b1b'),
                            borderRadius: '8px',
                            border: success ? 'none' : '1px solid rgba(220, 38, 38, 0.25)',
                            boxShadow: '0 12px 30px rgba(0, 0, 0, 0.16)',
                            fontFamily: '"IBM Plex Sans", "Segoe UI", sans-serif',
                            fontWeight: '500',
                            fontSize: '13.5px',
                        },
                    }).showToast();
                }, index * 160);
            });
        });
    </script>
    @endif
    <script>
        (function () {
            var loader = document.getElementById('page-loader');
            if (!loader) {
                return;
            }

            var hideLoader = function () {
                loader.classList.remove('is-active');
            };

            var showLoader = function () {
                loader.classList.add('is-active');
            };

            window.portalProgress = {
                start: showLoader,
                stop: hideLoader,
            };

            window.addEventListener('pageshow', hideLoader);
            window.addEventListener('load', hideLoader);

            document.addEventListener('submit', function (event) {
                if (event.defaultPrevented) {
                    return;
                }

                var form = event.target;
                if (form && (form.target === '_blank' || form.hasAttribute('download'))) {
                    return;
                }

                showLoader();
            });

            document.addEventListener('click', function (event) {
                var link = event.target.closest('a[href]');
                if (!link || event.defaultPrevented || event.button !== 0) {
                    return;
                }

                if (link.target === '_blank' || link.hasAttribute('download') || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                    return;
                }

                var url;
                try {
                    url = new URL(link.href, window.location.href);
                } catch (error) {
                    return;
                }

                if (url.origin !== window.location.origin) {
                    return;
                }

                if (url.pathname === window.location.pathname && url.search === window.location.search) {
                    return;
                }

                showLoader();
            });

            document.querySelectorAll('[data-open]').forEach(function (button) {
                button.addEventListener('click', function () {
                    var dialog = document.getElementById(button.getAttribute('data-open'));
                    if (dialog && typeof dialog.showModal === 'function') {
                        dialog.showModal();
                    }
                });
            });

            var pinTableHeads = function () {
                document.querySelectorAll('.table-wrap.has-complex-head').forEach(function (wrap) {
                    var sample = wrap.querySelector('thead tr:first-child th[colspan]');
                    if (!sample) {
                        return;
                    }

                    wrap.style.setProperty('--head-band', Math.max(Math.ceil(sample.getBoundingClientRect().height) - 1, 0) + 'px');
                });
            };

            pinTableHeads();
            window.addEventListener('resize', pinTableHeads);
            if (document.fonts && document.fonts.ready) {
                document.fonts.ready.then(pinTableHeads);
            }

            document.querySelectorAll('dialog.modal').forEach(function (dialog) {
                dialog.querySelectorAll('[data-close]').forEach(function (button) {
                    button.addEventListener('click', function () {
                        dialog.close();
                    });
                });

                dialog.addEventListener('click', function (event) {
                    if (event.target === dialog) {
                        dialog.close();
                    }
                });

                if (dialog.hasAttribute('data-show') && typeof dialog.showModal === 'function') {
                    dialog.showModal();
                }
            });
        })();
    </script>
</body>
</html>
