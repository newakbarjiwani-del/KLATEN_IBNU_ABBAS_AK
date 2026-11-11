@if (array_key_exists('groupKey', get_defined_vars()))
    @php
        $clearQuery = request()->except(['page', 'grup']);
        $clearHref = url()->current().($clearQuery === [] ? '' : '?'.http_build_query($clearQuery));
    @endphp
    <div class="group-status {{ $groupKey ? 'is-active' : '' }}" role="status">
        @if ($groupKey)
            <span>Dikelompokkan menurut <strong>{{ $groupLabel }}</strong></span>
            <a href="{{ $clearHref }}">Lepas</a>
        @else
            <span>Belum dikelompokkan. Klik judul kolom untuk mengelompokkan baris.</span>
        @endif
    </div>
@endif
