@extends('kesantrian.partials.shell')

@section('title', $current['label'].' — Kesantrian')

@section('desk')
    @include('kesantrian.partials.page-head', [
        'kicker' => $current['group'],
        'heading' => $current['label'],
        'summary' => $current['summary'],
    ])

    <div class="desk-empty">
        @include('partials.logo', ['class' => 'is-mark'])
        <p>Halaman {{ $current['label'] }} belum diisi.</p>
    </div>
@endsection
