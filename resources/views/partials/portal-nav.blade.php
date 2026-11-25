@php
    $sections = \App\Portal\PortalNav::sections($account);
    $beranda = $account->homeRoute();
@endphp
<nav class="desk-nav" id="desk-nav" aria-label="Menu">
    <a
        class="desk-link {{ $beranda !== null && request()->routeIs($beranda) ? 'is-active' : '' }}"
        href="{{ $beranda !== null ? route($beranda) : route('login') }}"
        @if ($beranda !== null && request()->routeIs($beranda)) aria-current="page" @endif
    >
        <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
            <polyline points="9 22 9 12 15 12 15 22"></polyline>
        </svg>
        <span>Dashboard Utama</span>
    </a>

    @foreach ($sections as $section)
        <p class="desk-system">{{ $section['name'] }}</p>
        @foreach ($section['groups'] as $group => $items)
            @if ($group !== $section['name'])
                <p class="desk-group">{{ $group }}</p>
            @endif
            @foreach ($items as $item)
                @php
                    $active = request()->routeIs($section['match']) && data_get($current ?? null, 'slug') === $item['slug'];
                @endphp
                <a
                    class="desk-link {{ $active ? 'is-active' : '' }}"
                    href="{{ route($section['menu'], $item['slug']) }}"
                    @if ($active) aria-current="page" @endif
                >
                    <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                    </svg>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        @endforeach
    @endforeach
</nav>
