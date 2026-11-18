@extends('kesantrian.partials.shell')

@section('title', $current['label'].' — Kesantrian')

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
        <form class="filter-sheet" method="GET" action="{{ route('kesantrian.menu', 'data-raport') }}">
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
                    @foreach ($classes as $class)
                        <option value="{{ $class }}" @selected($selectedClass === trim((string) $class))>{{ trim((string) $class) }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                Kelompok
                <select name="kelompok">
                    <option value="">Semua kelompok</option>
                    @foreach ($kelompok as $item)
                        <option value="{{ $item }}" @selected($selectedGroup === $item)>{{ $item }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                NIS / nama
                <input name="q" value="{{ $search }}" placeholder="Ketik NIS atau nama santri..." maxlength="70" autocomplete="off">
            </label>
            <div class="filter-actions">
                <a href="{{ route('kesantrian.menu', 'data-raport') }}" class="ghost" title="Reset filter">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;">
                        <polyline points="1 4 1 10 7 10"></polyline>
                        <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                    </svg>
                    Reset
                </a>
                <button type="submit" class="btn-primary">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    Cari
                </button>
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
                @include('partials.excel-link', ['href' => route('kesantrian.data-raport.export', request()->query())])
            </div>
        </div>
        @include('partials.group-status')
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <x-group-column label="NIS" column-key="NOCUST" :group-key="$groupKey" class="col-nis" />
                        <x-group-column label="Nama siswa" column-key="NMCUST" :group-key="$groupKey" class="col-name" />
                        <x-group-column label="Kelas" column-key="kelas" :group-key="$groupKey" />
                        <x-group-column label="Kelompok" column-key="kelompok" :group-key="$groupKey" />
                        <x-group-column label="Jenjang" column-key="jenjang" :group-key="$groupKey" />
                        <x-group-column label="Tahun akademik" column-key="THN_AKA" :group-key="$groupKey" />
                        <x-group-column label="Ujian" column-key="UJIAN" :group-key="$groupKey" />
                        <th>Tanggal upload</th>
                        <th>File raport</th>
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
                            <td class="col-nis font-mono">{{ $key }}</td>
                            <td class="col-name font-semibold">{{ trim((string) $record->NMCUST) }}</td>
                            <td>{{ trim((string) $record->kelas) }}</td>
                            <td>{{ trim((string) $record->kelompok) }}</td>
                            <td>{{ trim((string) $record->jenjang) }}</td>
                            <td>{{ trim((string) $record->THN_AKA) }}</td>
                            <td>
                                <span class="pill-item" style="padding: 2px 8px; font-size: 11.5px;">
                                    {{ $record->UJIAN === 'AKHIR' ? 'Akhir' : 'Mid' }}
                                </span>
                            </td>
                            <td>{{ $record->tanggal_upload }}</td>
                            <td>
                                @if ($record->tersedia)
                                    <a href="{{ $record->tautan }}" target="_blank" rel="noopener" class="btn-detail" title="Buka / unduh berkas PDF">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                            <polyline points="14 2 14 8 20 8"></polyline>
                                        </svg>
                                        {{ $record->FILE_RAPOT }}
                                    </a>
                                @else
                                    <span style="color: var(--ink-soft); font-size: 12px;">{{ $record->FILE_RAPOT }}</span>
                                @endif
                            </td>
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
