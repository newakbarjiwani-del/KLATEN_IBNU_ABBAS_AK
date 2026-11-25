@extends('kesantrian.partials.shell')

@section('title', 'Beranda — Ibnu Abbas Klaten')

@section('desk')
    @include('kesantrian.partials.page-head', [
        'kicker' => 'PPTQ Ibnu Abbas Klaten',
        'heading' => $account->portalTitle(),
        'summary' => $account->displayName().'. Menu di samping mengikuti hak akses akun ini.',
    ])

    @foreach ($sections as $section)
        <div class="portal-block">
            <h2>{{ $section['name'] }}</h2>
            <div class="desk-board">
                @foreach ($section['groups'] as $group => $items)
                    <section class="board-card">
                        <div class="board-card-head">
                            <h2>{{ $group }}</h2>
                            <span class="board-card-count">{{ count($items) }} Menu</span>
                        </div>
                        <ul>
                            @foreach ($items as $item)
                                <li>
                                    <a href="{{ route($section['menu'], $item['slug']) }}" class="board-item-link">
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
        </div>
    @endforeach
@endsection
