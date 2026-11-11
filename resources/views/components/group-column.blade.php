@props(['label', 'columnKey', 'groupKey' => null])

@php
    $isGrouped = $groupKey === $columnKey;
    $query = request()->except('page');
    unset($query['grup']);

    if (! $isGrouped) {
        $query['grup'] = $columnKey;
    }

    $href = url()->current().($query === [] ? '' : '?'.http_build_query($query));
@endphp

<th {{ $attributes }}>
    <a
        class="th-group {{ $isGrouped ? 'is-active' : '' }}"
        href="{{ $href }}"
        title="{{ $isGrouped ? 'Lepas pengelompokan' : 'Kelompokkan menurut '.$label }}"
    >
        <span>{{ $label }}</span>
        @if ($isGrouped)
            <span class="th-group-badge">Aktif</span>
        @else
            <svg class="th-group-icon" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M8 6h13"></path>
                <path d="M8 12h13"></path>
                <path d="M8 18h13"></path>
                <path d="M3 6h.01"></path>
                <path d="M3 12h.01"></path>
                <path d="M3 18h.01"></path>
            </svg>
        @endif
    </a>
</th>
