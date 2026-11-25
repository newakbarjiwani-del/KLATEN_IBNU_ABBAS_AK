@extends('tahfidz.partials.shell')

@section('title', $heading.' — Tahfidz')

@section('desk')
    @include('kesantrian.partials.page-head', [
        'kicker' => $current['group'],
        'heading' => $heading,
        'summary' => $current['summary'],
    ])

    <section class="panel-block">
        <div class="panel-block-head">
            <h2>Berkas</h2>
        </div>
        <div class="import-bar">
            <form class="filter-sheet" method="POST" action="{{ route('tahfidz.hafalan.open', $kind) }}" enctype="multipart/form-data">
                @csrf
                <label>
                    File
                    <input type="file" name="berkas" accept=".csv,.txt,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
                </label>
                <label>
                    Periode
                    <input name="periode" value="{{ $periode }}" maxlength="10" placeholder="0000-00" required>
                </label>
                <div class="filter-actions">
                    <button type="submit">Open</button>
                </div>
            </form>
            <div class="import-side">
                @include('partials.excel-link', [
                    'href' => route('tahfidz.hafalan.template', $kind),
                    'label' => 'Unduh template',
                ])
                <form method="POST" action="{{ route('tahfidz.hafalan.save', $kind) }}">
                    @csrf
                    <input type="hidden" name="periode" value="{{ $periode }}">
                    <button type="submit" class="btn-primary">Update</button>
                </form>
            </div>
        </div>
    </section>

    <section class="panel-block">
        <div class="panel-block-head">
            <h2>Pratinjau</h2>
            <div class="panel-block-meta">
                <span class="pill-item">
                    <span class="pill-label">Baris:</span>
                    <span class="pill-value is-highlight">{{ $openedCount }}</span>
                </span>
            </div>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>NIS</th>
                        <th>Nama</th>
                        @foreach ($kolom as $kolomBaris)
                            <th>{{ $kolomBaris['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($preview as $index => $row)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $row['nis'] }}</td>
                            <td>{{ $row['nama'] !== '' ? $row['nama'] : 'NIS tidak ditemukan' }}</td>
                            @foreach ($kolom as $kolomBaris)
                                <td>{{ $row[$kolomBaris['key']] ?? '' }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 3 + count($kolom) }}" class="text-center empty-state-box">
                                <p style="margin: 8px 0; color: var(--ink-soft); font-size: 14px;">Buka berkas untuk melihat isinya.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
