@extends('tahfidz.partials.shell')

@section('title', $current['label'].' — Tahfidz')

@section('desk')
    @include('kesantrian.partials.page-head', [
        'kicker' => $current['group'],
        'heading' => $current['label'],
        'summary' => $current['summary'],
    ])

    <section class="panel-block">
        <div class="panel-block-head">
            <h2>Filter</h2>
        </div>
        @if (($screen ?? '') === 'gabungan')
            <form class="filter-sheet" method="GET" action="{{ route('tahfidz.menu', $current['slug']) }}">
                @include('partials.keep-grup')
                <label>
                    Tanggal mulai
                    <input type="date" name="dari" value="{{ $dari }}">
                </label>
                <label>
                    Tanggal akhir
                    <input type="date" name="sampai" value="{{ $sampai }}">
                </label>
                <label>
                    Mode
                    <select name="mode">
                        <option value="">Semua</option>
                        <option value="setoran" @selected($mode === 'setoran')>Setoran</option>
                        <option value="murajaah" @selected($mode === 'murajaah')>Murajaah</option>
                    </select>
                </label>
                <label>
                    Periode
                    <select name="periode">
                        <option value="">Semua</option>
                        @foreach ($periods as $period)
                            <option value="{{ $period }}" @selected($selectedPeriod === $period)>{{ $period }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="filter-actions">
                    <a href="{{ route('tahfidz.menu', $current['slug']) }}" class="ghost">Reset</a>
                    <button type="submit">Cari</button>
                </div>
            </form>
        @else
            <form class="filter-sheet" method="GET" action="{{ route('tahfidz.menu', $current['slug']) }}">
                @include('partials.keep-grup')
                <label>
                    {{ $periodLabel }}
                    <select name="periode">
                        <option value="">Semua</option>
                        @foreach ($periods as $period)
                            <option value="{{ $period }}" @selected($selectedPeriod === $period)>{{ $period }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    NIS / nama
                    <input name="q" value="{{ $search }}" placeholder="Ketik NIS atau nama santri..." maxlength="70" autocomplete="off">
                </label>
                <div class="filter-actions">
                    <a href="{{ route('tahfidz.menu', $current['slug']) }}" class="ghost">Reset</a>
                    <button type="submit">Cari</button>
                </div>
            </form>
        @endif
    </section>

    <section class="panel-block">
        <div class="panel-block-head">
            <h2>{{ $current['label'] }}</h2>
            <div class="panel-block-meta">
                <span class="pill-item">
                    <span class="pill-label">Total Data:</span>
                    <span class="pill-value is-highlight">{{ $records->total() }}</span>
                </span>
                @include('partials.excel-link', [
                    'href' => ($screen ?? '') === 'gabungan'
                        ? route('tahfidz.data-setoran.export', request()->query())
                        : route('tahfidz.setoran-lama.export', request()->query()),
                ])
            </div>
        </div>
        @include('partials.group-status')
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        @foreach ($columns as $column)
                            <x-group-column
                                :label="$column['label']"
                                :column-key="$column['key']"
                                :group-key="$groupKey"
                                @class([
                                    'col-nis' => $column['key'] === 'NOCUST',
                                    'col-name' => $column['key'] === 'NMCUST',
                                    'col-date' => $column['key'] === 'tgl',
                                    'col-thn' => in_array($column['key'], ['periode', 'thn_aka'], true),
                                ])
                            />
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @php $groupBand = null; @endphp
                    @forelse ($records as $record)
                        @if ($groupKey)
                            @php $groupValue = \App\Tables\ColumnGroup::text($record, $groupKey); @endphp
                            @if ($groupBand !== $groupValue)
                                <x-group-band :label="$groupLabel" :value="$groupValue" :span="count($columns)" />
                                @php $groupBand = $groupValue; @endphp
                            @endif
                        @endif
                        <tr>
                            @foreach ($columns as $column)
                                <td class="{{ match ($column['key']) { 'NOCUST' => 'col-nis', 'NMCUST' => 'col-name', 'tgl' => 'col-date', 'periode', 'thn_aka' => 'col-thn', default => '' } }}">
                                    @if ($column['key'] === 'tgl' && filled($record->tgl))
                                        {{ \Illuminate\Support\Str::of((string) $record->tgl)->substr(0, 10) }}
                                    @else
                                        {{ trim((string) ($record->{$column['key']} ?? '')) }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($columns) }}" class="text-center empty-state-box">
                                <p style="margin: 8px 0; color: var(--ink-soft); font-size: 14px;">Belum ada data.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($records->hasPages())
            <div class="pagination-wrap">
                {{ $records->links('kesantrian.partials.pagination') }}
            </div>
        @endif
    </section>
@endsection
