<header class="desk-head">
    <div class="desk-head-content">
        <div class="desk-head-titles">
            <p class="kicker">{{ $kicker }}</p>
            <h1>{{ $heading }}</h1>
            @if (! empty($summary))
                <p class="desk-lead">{{ $summary }}</p>
            @endif
        </div>
        <div class="desk-head-meta">
            <span class="head-badge-portal">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                <span>Back Office PPTQ Ibnu Abbas</span>
            </span>
        </div>
    </div>
</header>
