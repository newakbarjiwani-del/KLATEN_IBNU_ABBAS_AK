@extends('akademik.partials.shell')

@section('title', $current['label'].' — Raport Akademik')

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
        <form class="filter-sheet" method="GET" action="{{ route('akademik.menu', 'data-raport') }}">
            @include('partials.keep-grup')
            <label>
                Tahun akademik
                <select name="thn_aka">
                    <option value="">Semua tahun</option>
                    @foreach ($years as $year)
                        <option value="{{ $year->thn_aka }}" @selected($selectedYear === $year->thn_aka)>{{ $year->thn_aka }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                Kelas
                <select name="kelas">
                    <option value="">Semua kelas</option>
                    @foreach ($classes as $pilihanKelas)
                        <option value="{{ $pilihanKelas['kunci'] }}" @selected($selectedClass === $pilihanKelas['kunci'])>{{ $pilihanKelas['label'] }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                NIS / nama
                <input name="q" value="{{ $search }}" placeholder="Ketik NIS atau nama santri..." maxlength="70" autocomplete="off">
            </label>
            <div class="filter-actions">
                <a href="{{ route('akademik.menu', 'data-raport') }}" class="ghost">Reset</a>
                <button type="submit">Cari</button>
            </div>
        </form>
    </section>

    <section class="panel-block">
        <div class="panel-block-head">
            <h2>Berkas</h2>
            <div class="panel-block-meta">
                <span class="pill-item">
                    <span class="pill-label">Total Data:</span>
                    <span class="pill-value is-highlight">{{ $records->total() }} berkas</span>
                </span>
                @include('partials.excel-link', ['href' => route('akademik.data-raport.export', request()->query())])
            </div>
        </div>
        @include('partials.group-status')
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <x-group-column label="NIS" column-key="NOCUST" :group-key="$groupKey" />
                        <x-group-column label="Nama siswa" column-key="NMCUST" :group-key="$groupKey" />
                        <th>File raport</th>
                        <x-group-column label="Jenjang" column-key="jenjang" :group-key="$groupKey" />
                        <x-group-column label="Kelas" column-key="kelas" :group-key="$groupKey" />
                        <x-group-column label="Kelompok" column-key="kelompok" :group-key="$groupKey" />
                        <x-group-column label="Tahun akademik" column-key="THN_AKA" :group-key="$groupKey" />
                        <x-group-column label="Ujian" column-key="UJIAN" :group-key="$groupKey" />
                        <th>Tanggal upload</th>
                    </tr>
                </thead>
                <tbody>
                    @php $group = null; @endphp
                    @forelse ($records as $record)
                        @php $key = trim((string) $record->NOCUST); @endphp
                        @if ($groupKey)
                            @php $groupValue = \App\Tables\ColumnGroup::text($record, $groupKey); @endphp
                            @if ($group !== $groupValue)
                                <x-group-band :label="$groupLabel" :value="$groupValue" span="9" />
                                @php $group = $groupValue; @endphp
                            @endif
                        @elseif ($group !== $key)
                            <tr class="row-group">
                                <td colspan="9">
                                    <strong>{{ trim((string) $record->NMCUST) !== '' ? trim((string) $record->NMCUST) : 'Tanpa nama' }}</strong>
                                    <span>NIS: {{ $key }}</span>
                                </td>
                            </tr>
                            @php $group = $key; @endphp
                        @endif
                        <tr>
                            <td>{{ $key }}</td>
                            <td>{{ trim((string) $record->NMCUST) }}</td>
                            <td>
                                @if ($record->tersedia)
                                    <a href="{{ $record->tautan }}" target="_blank" rel="noopener">{{ $record->FILE_RAPOT }}</a>
                                @else
                                    {{ $record->FILE_RAPOT }}
                                @endif
                            </td>
                            <td>{{ trim((string) $record->jenjang) }}</td>
                            <td>{{ trim((string) $record->kelas) }}</td>
                            <td>{{ trim((string) $record->kelompok) }}</td>
                            <td>{{ trim((string) $record->THN_AKA) }}</td>
                            <td>{{ $record->UJIAN === 'AKHIR' ? 'Akhir' : 'Mid' }}</td>
                            <td>{{ $record->tanggal_upload }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center empty-state-box">
                                <p style="margin: 8px 0; color: var(--ink-soft); font-size: 14px;">Belum ada raport.</p>
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
