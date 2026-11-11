@if ($paginator->hasPages())
    @php
        $total = $paginator->lastPage();
        $current = $paginator->currentPage();
        $range = 2; // number of pages to show on each side of current
    @endphp
    <nav class="portal-pagination" role="navigation" aria-label="Navigasi Halaman">
        <div class="pagination-pages">
            @if ($paginator->onFirstPage())
                <span class="pagination-btn is-disabled" aria-disabled="true">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                    <span>Sebelumnya</span>
                </span>
            @else
                <a class="pagination-btn" href="{{ $paginator->previousPageUrl() }}" rel="prev">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                    <span>Sebelumnya</span>
                </a>
            @endif

            @if ($total > 1)
                @if ($current > $range + 2)
                    <a class="pagination-btn is-page" href="{{ $paginator->url(1) }}">1</a>
                    <span class="pagination-ellipsis">&hellip;</span>
                @endif
                @foreach (range(max(1, $current - $range), min($total, $current + $range)) as $page)
                    @if ($page == $current)
                        <span class="pagination-btn is-page is-active" aria-current="page">{{ $page }}</span>
                    @else
                        <a class="pagination-btn is-page" href="{{ $paginator->url($page) }}">{{ $page }}</a>
                    @endif
                @endforeach
                @if ($current < $total - $range - 1)
                    <span class="pagination-ellipsis">&hellip;</span>
                    <a class="pagination-btn is-page" href="{{ $paginator->url($total) }}">{{ $total }}</a>
                @endif
            @endif

            @if ($paginator->hasMorePages())
                <a class="pagination-btn" href="{{ $paginator->nextPageUrl() }}" rel="next">
                    <span>Selanjutnya</span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </a>
            @else
                <span class="pagination-btn is-disabled" aria-disabled="true">
                    <span>Selanjutnya</span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </span>
            @endif
        </div>

        <form class="pagination-jump" method="GET" action="{{ url()->current() }}">
            @foreach (request()->except('page') as $name => $value)
                @continue(is_array($value))
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endforeach
            <span class="pagination-jump-label">Ke halaman</span>
            <input
                class="pagination-page-input"
                type="number"
                name="page"
                min="1"
                max="{{ $total }}"
                value="{{ $current }}"
                inputmode="numeric"
                aria-label="Nomor halaman"
                required
            >
            <span class="pagination-jump-total">dari <strong class="page-total">{{ number_format($total, 0, ',', '.') }}</strong></span>
            <button type="submit">Buka</button>
            <span class="pagination-count">{{ number_format($paginator->total(), 0, ',', '.') }} data</span>
        </form>
    </nav>
    @once
        <script>
            document.querySelectorAll('.pagination-jump').forEach(function (form) {
                form.addEventListener('submit', function () {
                    var input = form.querySelector('input[name="page"]');
                    if (!input) {
                        return;
                    }

                    var max = Number(input.getAttribute('max')) || 1;
                    var page = Math.floor(Number(input.value));
                    if (!Number.isFinite(page) || page < 1) {
                        page = 1;
                    }
                    if (page > max) {
                        page = max;
                    }
                    input.value = String(page);
                });
            });
        </script>
    @endonce
@endif
