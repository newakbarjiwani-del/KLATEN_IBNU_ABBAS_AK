@extends('kesantrian.partials.shell')

@section('title', 'Import Raport Kesantrian — Kesantrian')

@section('desk')
    @include('kesantrian.partials.page-head', [
        'kicker' => $current['group'],
        'heading' => 'Import Raport Kesantrian',
        'summary' => 'Unggah berkas Excel (.xlsx) atau CSV untuk input dan perbarui nilai raport kesantrian santri.',
    ])

    @if (session('status'))
        <div class="alert is-status">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="alert is-danger">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert is-danger">
            <div>
                <strong>Terjadi kesalahan input:</strong>
                <ul style="margin: 6px 0 0 18px; padding: 0;">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    @include('kesantrian.partials.unit-tabs', ['menu' => 'input-raport-kesantrian'])

    @php
        $punyaButir = $kategori->contains(fn ($kelompok) => $kelompok->items->isNotEmpty());
    @endphp

    @if (! $punyaButir)
        <section class="panel-block">
            <p class="empty-state-box">
                Kategori raport untuk unit {{ $selectedUnit }} belum dibuat.
                <a href="{{ route('kesantrian.menu', ['menu' => 'master-kategori-raport', 'unit' => $selectedUnit]) }}">Buka Master Kategori Raport</a>
            </p>
        </section>
    @else
    <section class="panel-block">
        <div class="panel-block-head">
            <h2>Form Import Raport Kesantrian</h2>
            <div class="panel-block-meta">
                <a href="{{ route('kesantrian.input-raport-kesantrian.template', ['format' => 'xlsx', 'unit' => $selectedUnit]) }}" class="btn-template-excel" download="template_import_raport_kesantrian.xlsx" title="Unduh Template Excel">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 6px;">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="12" y1="18" x2="12" y2="12"></line>
                        <line x1="9" y1="15" x2="15" y2="15"></line>
                    </svg>
                    Unduh Template Excel (.xlsx)
                </a>
                <a href="{{ route('kesantrian.input-raport-kesantrian.template', ['format' => 'csv', 'unit' => $selectedUnit]) }}" class="btn-template-csv" download="template_import_raport_kesantrian.csv" title="Unduh Template CSV">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 6px;">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                    </svg>
                    Unduh Template (.csv)
                </a>
            </div>
        </div>

        <form class="import-form-grid" id="form-preview" method="POST" action="{{ route('kesantrian.input-raport-kesantrian.preview') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="unit" value="{{ $selectedUnit }}">

            <div class="import-field-file">
                <label for="file-upload">
                    Berkas Raport (.xlsx / .csv)
                </label>
                <div class="file-picker-box" id="file-picker-dropzone">
                    <input type="file" name="file" id="file-upload" accept=".xlsx,.csv,.txt" required>
                    <div class="file-picker-inner">
                        <svg class="file-picker-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                        <span class="file-chosen-text" id="file-chosen-name">
                            {{ $previewFilename ? $previewFilename : 'Belum ada berkas dipilih (Klik atau seret berkas ke sini)' }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="import-field">
                <label for="periode-select">
                    Periode
                </label>
                <select name="periode" id="periode-select" required>
                    @foreach ($years as $year)
                        <option value="{{ $year->thn_aka }}" @selected($selectedYear === $year->thn_aka)>
                            {{ $year->thn_aka }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="import-field">
                <label for="tgl-input">
                    Tanggal Raport
                </label>
                <input type="date" name="tgl" id="tgl-input" value="{{ old('tgl', $selectedDate) }}" required>
            </div>

            <div class="import-actions">
                <button type="submit" class="btn-import-open" title="Buka berkas dan tampilkan preview isi tabel">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 6px;">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                    OPEN (Preview)
                </button>
            </div>
        </form>

        @if (!empty($previewRows))
            <form id="form-save-raport" method="POST" action="{{ route('kesantrian.input-raport-kesantrian.store') }}" style="margin: 0; padding: 0 20px 20px;">
                @csrf
                <input type="hidden" name="periode" value="{{ $selectedYear }}">
                <input type="hidden" name="tgl" value="{{ $selectedDate }}">
                <input type="hidden" name="rows_json" value="{{ json_encode($previewRows) }}">
                <input type="hidden" name="unit" value="{{ $selectedUnit }}">

                <div class="preview-toolbar">
                    <div class="preview-stats">
                        <span class="pill-item">
                            <span class="pill-label">Total Baris:</span>
                            <span class="pill-value is-highlight">{{ count($previewRows) }}</span>
                        </span>
                        <span class="pill-item">
                            <span class="pill-label">Santri Terdaftar:</span>
                            <span class="pill-value is-success">{{ count(array_filter($previewRows, fn($r) => !empty($r['santri_ditemukan']))) }}</span>
                        </span>
                        @php
                            $unknownCount = count(array_filter($previewRows, fn($r) => empty($r['santri_ditemukan'])));
                        @endphp
                        @if ($unknownCount > 0)
                            <span class="pill-item">
                                <span class="pill-label">NIS Belum Ada di DB:</span>
                                <span class="pill-value is-danger">{{ $unknownCount }}</span>
                            </span>
                        @endif
                        <span class="pill-item">
                            <span class="pill-label">Periode:</span>
                            <span class="pill-value">{{ $selectedYear }}</span>
                        </span>
                    </div>

                    <button type="submit" class="btn-import-update" id="btn-update-raport">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 6px;">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                            <polyline points="17 21 17 13 7 13 7 21"></polyline>
                            <polyline points="7 3 7 8 15 8"></polyline>
                        </svg>
                        UPDATE (Simpan ke Raport)
                    </button>
                </div>
            </form>
        @endif
    </section>

    <section class="panel-block">
        <div class="panel-block-head">
            <h2>
                @if (!empty($previewRows))
                    Preview Data Berkas ({{ count($previewRows) }} baris) — Siap Disimpan
                @else
                    Data Raport Terkini (Periode: {{ $selectedYear }})
                @endif
            </h2>
            <div class="panel-block-meta">
                <a href="{{ route('kesantrian.menu', ['menu' => 'data-raport-kesantrian', 'periode' => $selectedYear, 'unit' => $selectedUnit]) }}" class="view-all-link">
                    Buka Halaman Data Raport Kesantrian &rarr;
                </a>
            </div>
        </div>

        <div class="table-wrap has-complex-head">
            <table class="data-table is-raport" id="tabel-raport-kesantrian">
                <thead>
                    <tr>
                        <th rowspan="2" class="col-num">ID</th>
                        <th rowspan="2" class="col-nis">NIS</th>
                        <th rowspan="2" class="col-name">Nama</th>
                        @foreach ($kategori as $index => $kelompok)
                            @continue($kelompok->items->isEmpty())
                            @php $gaya = ['group-ibadah', 'group-adab', 'group-pribadi', 'group-diri'][$index % 4]; @endphp
                            <th colspan="{{ $kelompok->items->count() }}" class="col-group-head {{ $gaya }}">{{ $kelompok->nama }}</th>
                        @endforeach
                        <th class="col-group-head group-catatan">Catatan</th>
                    </tr>
                    <tr>
                        @foreach ($kategori as $index => $kelompok)
                            @continue($kelompok->items->isEmpty())
                            @php $gaya = ['group-ibadah-sub', 'group-adab-sub', 'group-pribadi-sub', 'group-diri-sub'][$index % 4]; @endphp
                            @foreach ($kelompok->items as $item)
                                <th class="col-grade {{ $gaya }}" title="{{ $item->nama }}">{{ $item->nama }}</th>
                            @endforeach
                        @endforeach
                        <th class="col-catatan">Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $jumlahKolom = 4 + $kategori->sum(fn ($kelompok) => $kelompok->items->count());
                    @endphp
                    @if (!empty($previewRows))
                        @foreach ($previewRows as $index => $row)
                            <tr class="{{ empty($row['santri_ditemukan']) || empty($row['unit_sesuai']) ? 'row-warning' : '' }}">
                                <td class="col-num">{{ $index + 1 }}</td>
                                <td class="col-nis font-mono">
                                    {{ $row['nis'] }}
                                    @if (empty($row['santri_ditemukan']))
                                        <span class="badge-not-found" title="NIS belum terdaftar di database santri">NIS Belum Ada</span>
                                    @elseif (empty($row['unit_sesuai']))
                                        <span class="badge-not-found" title="Santri bukan unit ini">Bukan unit ini</span>
                                    @else
                                        <span class="badge-found" title="Santri terdaftar di database">✓</span>
                                    @endif
                                </td>
                                <td class="col-name font-semibold">
                                    {{ $row['nama'] ?: '(NIS tidak terdaftar di database)' }}
                                </td>
                                @foreach ($kategori as $kelompok)
                                    @foreach ($kelompok->items as $item)
                                        @include('kesantrian.partials.raport-nilai', ['nilai' => $row['nilai'][$item->id] ?? ''])
                                    @endforeach
                                @endforeach
                                <td class="col-catatan" title="{{ $row['catatan'] }}">{{ $row['catatan'] ?: '—' }}</td>
                            </tr>
                        @endforeach
                    @elseif (!empty($recentSaved) && count($recentSaved) > 0)
                        @foreach ($recentSaved as $index => $r)
                            <tr>
                                <td class="col-num">{{ $index + 1 }}</td>
                                <td class="col-nis font-mono">{{ trim((string) $r->NOCUST) }}</td>
                                <td class="col-name font-semibold">{{ trim((string) $r->NMCUST) ?: '-' }}</td>
                                @foreach ($kategori as $kelompok)
                                    @foreach ($kelompok->items as $item)
                                        @include('kesantrian.partials.raport-nilai', ['nilai' => $r->nilai[$item->id] ?? ''])
                                    @endforeach
                                @endforeach
                                <td class="col-catatan" title="{{ $r->Catatan }}">{{ $r->Catatan ?: '—' }}</td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="{{ $jumlahKolom }}" class="text-center empty-state-box">
                                <p style="margin: 8px 0; color: var(--ink-soft); font-size: 14px;">
                                    Belum ada berkas yang di-preview. Pilih berkas Excel/CSV di atas lalu klik tombol <strong>OPEN (Preview)</strong>.
                                </p>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </section>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var fileInput = document.getElementById('file-upload');
            var dropzone = document.getElementById('file-picker-dropzone');
            var chosenText = document.getElementById('file-chosen-name');

            if (fileInput && chosenText) {
                fileInput.addEventListener('change', function () {
                    if (this.files && this.files.length > 0) {
                        var file = this.files[0];
                        chosenText.textContent = '📄 ' + file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB) — Berkas Siap';
                        chosenText.style.color = 'var(--brand)';
                        chosenText.style.fontWeight = '600';
                    }
                });
            }

            if (dropzone && fileInput) {
                ['dragenter', 'dragover'].forEach(function (eventName) {
                    dropzone.addEventListener(eventName, function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        dropzone.classList.add('is-dragover');
                    });
                });

                ['dragleave', 'drop'].forEach(function (eventName) {
                    dropzone.addEventListener(eventName, function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        dropzone.classList.remove('is-dragover');
                    });
                });

                dropzone.addEventListener('drop', function (e) {
                    if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                        fileInput.files = e.dataTransfer.files;
                        fileInput.dispatchEvent(new Event('change'));
                    }
                });
            }

            var formSave = document.getElementById('form-save-raport');
            if (formSave) {
                formSave.addEventListener('submit', function (e) {
                    var count = {{ count($previewRows) }};
                    var periode = document.getElementById('periode-select') ? document.getElementById('periode-select').value : '';
                    if (!confirm('Apakah Anda yakin ingin menyimpan ' + count + ' data raport kesantrian untuk periode ' + periode + '? Data yang sudah ada dengan NIS dan Periode sama akan diperbarui.')) {
                        e.preventDefault();
                    }
                });
            }
        });
    </script>
@endsection
