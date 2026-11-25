@extends('tahfidz.partials.shell')

@section('title', 'Laporan Capaian — Tahfidz')

@section('desk')
    @include('kesantrian.partials.page-head', [
        'kicker' => $current['group'],
        'heading' => $current['label'],
        'summary' => $current['summary'],
    ])

    <section class="panel-block">
        <div class="panel-block-head">
            <h2>Bulan</h2>
        </div>
        <form class="filter-sheet" method="GET" action="{{ route('tahfidz.menu', $current['slug']) }}">
            <div class="bulan-pilih">
                @forelse ($periods as $period)
                    <label>
                        <input type="checkbox" name="bulan[]" value="{{ $period }}" @checked(in_array($period, $bulan, true))>
                        {{ $namaPeriode[$period] ?? $period }}
                    </label>
                @empty
                    <p>Belum ada bulan. Unggah data tahfidz terlebih dahulu.</p>
                @endforelse
            </div>
            <label>
                NIS / nama
                <input name="q" value="{{ $search }}" placeholder="Ketik NIS atau nama santri..." maxlength="70" autocomplete="off">
            </label>
            <div class="filter-actions">
                <a href="{{ route('tahfidz.menu', $current['slug']) }}" class="ghost">Reset</a>
                <button type="submit">Tampilkan</button>
                @if ($bulan !== [])
                    <a class="btn-primary" href="{{ route('tahfidz.laporan-capaian.cetak', request()->query()) }}">Cetak PDF</a>
                @endif
            </div>
        </form>
    </section>

    <section class="panel-block">
        <div class="panel-block-head">
            <h2>Capaian</h2>
            <div class="panel-block-meta">
                <span class="pill-item">
                    <span class="pill-label">Total Data:</span>
                    <span class="pill-value is-highlight">{{ $records?->total() ?? 0 }}</span>
                </span>
            </div>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Bulan</th>
                        <th>NIS</th>
                        <th>Nama</th>
                        @foreach ($kolom as $kolomBaris)
                            <th>{{ $kolomBaris['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records ?? [] as $record)
                        <tr>
                            <td>{{ $record->periode }}</td>
                            <td class="col-nis">{{ trim((string) $record->NOCUST) }}</td>
                            <td class="col-name">{{ trim((string) $record->NMCUST) }}</td>
                            @foreach ($kolom as $kolomBaris)
                                <td>{{ trim((string) ($record->{$kolomBaris['key']} ?? '')) }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 3 + count($kolom) }}" class="text-center empty-state-box">
                                <p style="margin: 8px 0; color: var(--ink-soft); font-size: 14px;">
                                    {{ $bulan === [] ? 'Pilih satu bulan atau beberapa bulan sekaligus.' : 'Belum ada data pada bulan yang dipilih.' }}
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($records?->hasPages())
            <div class="pagination-wrap">
                {{ $records->links('kesantrian.partials.pagination') }}
            </div>
        @endif
    </section>
@endsection
