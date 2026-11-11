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

    <section class="panel-block">
        <div class="panel-block-head">
            <h2>Form Import Raport Kesantrian</h2>
            <div class="panel-block-meta">
                <a href="{{ route('kesantrian.input-raport-kesantrian.template', ['format' => 'xlsx']) }}" class="btn-template-excel" download="template_import_raport_kesantrian.xlsx" title="Unduh Template Excel">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 6px;">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="12" y1="18" x2="12" y2="12"></line>
                        <line x1="9" y1="15" x2="15" y2="15"></line>
                    </svg>
                    Unduh Template Excel (.xlsx)
                </a>
                <a href="{{ route('kesantrian.input-raport-kesantrian.template', ['format' => 'csv']) }}" class="btn-template-csv" download="template_import_raport_kesantrian.csv" title="Unduh Template CSV">
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
                <a href="{{ route('kesantrian.menu', ['menu' => 'data-raport-kesantrian', 'periode' => $selectedYear]) }}" class="view-all-link">
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
                        <th colspan="5" class="col-group-head group-ibadah">Ibadah</th>
                        <th colspan="5" class="col-group-head group-adab">Adab</th>
                        <th colspan="5" class="col-group-head group-pribadi">Kepribadian</th>
                        <th colspan="3" class="col-group-head group-diri">Pengembangan Diri</th>
                        <th class="col-group-head group-catatan">Catatan</th>
                    </tr>
                    <tr>
                        <th class="col-grade group-ibadah-sub" title="Sholat Wajib">Sholat Wajib</th>
                        <th class="col-grade group-ibadah-sub" title="Sholat Dhuha">Sholat Dhuha</th>
                        <th class="col-grade group-ibadah-sub" title="Sholat Lail">Sholat Lail</th>
                        <th class="col-grade group-ibadah-sub" title="Puasa Sunnah">Puasa Sunnah</th>
                        <th class="col-grade group-ibadah-sub" title="Sedekah">Sedekah</th>

                        <th class="col-grade group-adab-sub" title="Menghormati">Menghormati</th>
                        <th class="col-grade group-adab-sub" title="Berkata Sopan">Berkata Sopan</th>
                        <th class="col-grade group-adab-sub" title="Jujur">Jujur</th>
                        <th class="col-grade group-adab-sub" title="Tolong Menolong">Tolong Menolong</th>
                        <th class="col-grade group-adab-sub" title="Mengajak Kebaikan">Mengajak Kebaikan</th>

                        <th class="col-grade group-pribadi-sub" title="Kebersihan Diri">Kebersihan Diri</th>
                        <th class="col-grade group-pribadi-sub" title="Kebersihan Lingkungan">Kebersihan Lingkungan</th>
                        <th class="col-grade group-pribadi-sub" title="Hubungan Sosial">Hubungan Sosial</th>
                        <th class="col-grade group-pribadi-sub" title="Disiplin">Disiplin</th>
                        <th class="col-grade group-pribadi-sub" title="Kesehatan">Kesehatan</th>

                        <th class="col-diri group-diri-sub" title="Pengembangan Diri">Pengembangan Diri</th>
                        <th class="col-diri group-diri-sub" title="Aktif Organisasi">Aktif Organisasi</th>
                        <th class="col-diri group-diri-sub" title="Literasi Buku">Literasi Buku</th>

                        <th class="col-catatan" title="Wali Hujroh">Wali Hujroh</th>
                    </tr>
                </thead>
                <tbody>
                    @if (!empty($previewRows))
                        @foreach ($previewRows as $index => $row)
                            <tr class="{{ empty($row['santri_ditemukan']) ? 'row-warning' : '' }}">
                                <td class="col-num">{{ $index + 1 }}</td>
                                <td class="col-nis font-mono">
                                    {{ $row['nis'] }}
                                    @if (empty($row['santri_ditemukan']))
                                        <span class="badge-not-found" title="NIS belum terdaftar di database santri">NIS Belum Ada</span>
                                    @else
                                        <span class="badge-found" title="Santri terdaftar di database">✓</span>
                                    @endif
                                </td>
                                <td class="col-name font-semibold">
                                    {{ $row['nama'] ?: '(NIS tidak terdaftar di database)' }}
                                </td>
                                <td class="col-grade">{!! $row['ibadah1'] ? '<span class="grade-badge grade-'.strtolower($row['ibadah1']).'">'.$row['ibadah1'].'</span>' : '<span class="dash-empty">—</span>' !!}</td>
                                <td class="col-grade">{!! $row['ibadah2'] ? '<span class="grade-badge grade-'.strtolower($row['ibadah2']).'">'.$row['ibadah2'].'</span>' : '<span class="dash-empty">—</span>' !!}</td>
                                <td class="col-grade">{!! $row['ibadah3'] ? '<span class="grade-badge grade-'.strtolower($row['ibadah3']).'">'.$row['ibadah3'].'</span>' : '<span class="dash-empty">—</span>' !!}</td>
                                <td class="col-grade">{!! $row['ibadah4'] ? '<span class="grade-badge grade-'.strtolower($row['ibadah4']).'">'.$row['ibadah4'].'</span>' : '<span class="dash-empty">—</span>' !!}</td>
                                <td class="col-grade">{!! $row['ibadah5'] ? '<span class="grade-badge grade-'.strtolower($row['ibadah5']).'">'.$row['ibadah5'].'</span>' : '<span class="dash-empty">—</span>' !!}</td>

                                <td class="col-grade">{!! $row['adab1'] ? '<span class="grade-badge grade-'.strtolower($row['adab1']).'">'.$row['adab1'].'</span>' : '<span class="dash-empty">—</span>' !!}</td>
                                <td class="col-grade">{!! $row['adab2'] ? '<span class="grade-badge grade-'.strtolower($row['adab2']).'">'.$row['adab2'].'</span>' : '<span class="dash-empty">—</span>' !!}</td>
                                <td class="col-grade">{!! $row['adab3'] ? '<span class="grade-badge grade-'.strtolower($row['adab3']).'">'.$row['adab3'].'</span>' : '<span class="dash-empty">—</span>' !!}</td>
                                <td class="col-grade">{!! $row['adab4'] ? '<span class="grade-badge grade-'.strtolower($row['adab4']).'">'.$row['adab4'].'</span>' : '<span class="dash-empty">—</span>' !!}</td>
                                <td class="col-grade">{!! $row['adab5'] ? '<span class="grade-badge grade-'.strtolower($row['adab5']).'">'.$row['adab5'].'</span>' : '<span class="dash-empty">—</span>' !!}</td>

                                <td class="col-grade">{!! $row['pribadi1'] ? '<span class="grade-badge grade-'.strtolower($row['pribadi1']).'">'.$row['pribadi1'].'</span>' : '<span class="dash-empty">—</span>' !!}</td>
                                <td class="col-grade">{!! $row['pribadi2'] ? '<span class="grade-badge grade-'.strtolower($row['pribadi2']).'">'.$row['pribadi2'].'</span>' : '<span class="dash-empty">—</span>' !!}</td>
                                <td class="col-grade">{!! $row['pribadi3'] ? '<span class="grade-badge grade-'.strtolower($row['pribadi3']).'">'.$row['pribadi3'].'</span>' : '<span class="dash-empty">—</span>' !!}</td>
                                <td class="col-grade">{!! $row['pribadi4'] ? '<span class="grade-badge grade-'.strtolower($row['pribadi4']).'">'.$row['pribadi4'].'</span>' : '<span class="dash-empty">—</span>' !!}</td>
                                <td class="col-grade">{!! $row['pribadi5'] ? '<span class="grade-badge grade-'.strtolower($row['pribadi5']).'">'.$row['pribadi5'].'</span>' : '<span class="dash-empty">—</span>' !!}</td>

                                <td class="col-diri" title="{{ $row['diri1'] }}">{{ $row['diri1'] ?: '—' }}</td>
                                <td class="col-diri" title="{{ $row['diri2'] }}">{{ $row['diri2'] ?: '—' }}</td>
                                <td class="col-diri" title="{{ $row['diri3'] }}">{{ $row['diri3'] ?: '—' }}</td>

                                <td class="col-catatan" title="{{ $row['catatan'] }}">{{ $row['catatan'] ?: '—' }}</td>
                            </tr>
                        @endforeach
                    @elseif (!empty($recentSaved) && count($recentSaved) > 0)
                        @foreach ($recentSaved as $index => $r)
                            <tr>
                                <td class="col-num">{{ $index + 1 }}</td>
                                <td class="col-nis font-mono">{{ trim((string) $r->NOCUST) }}</td>
                                <td class="col-name font-semibold">{{ trim((string) $r->NMCUST) ?: '-' }}</td>
                                <td class="col-grade">{!! $r->IbadahA ? '<span class="grade-badge grade-'.strtolower($r->IbadahA).'">'.$r->IbadahA.'</span>' : '<span class="dash-empty">—</span>' !!}</td>
                                <td class="col-grade">{!! $r->IbadahB ? '<span class="grade-badge grade-'.strtolower($r->IbadahB).'">'.$r->IbadahB.'</span>' : '<span class="dash-empty">—</span>' !!}</td>
                                <td class="col-grade">{!! $r->IbadahC ? '<span class="grade-badge grade-'.strtolower($r->IbadahC).'">'.$r->IbadahC.'</span>' : '<span class="dash-empty">—</span>' !!}</td>
                                <td class="col-grade">{!! $r->IbadahD ? '<span class="grade-badge grade-'.strtolower($r->IbadahD).'">'.$r->IbadahD.'</span>' : '<span class="dash-empty">—</span>' !!}</td>
                                <td class="col-grade">{!! $r->IbadahE ? '<span class="grade-badge grade-'.strtolower($r->IbadahE).'">'.$r->IbadahE.'</span>' : '<span class="dash-empty">—</span>' !!}</td>

                                <td class="col-grade">{!! $r->AdabA ? '<span class="grade-badge grade-'.strtolower($r->AdabA).'">'.$r->AdabA.'</span>' : '<span class="dash-empty">—</span>' !!}</td>
                                <td class="col-grade">{!! $r->AdabB ? '<span class="grade-badge grade-'.strtolower($r->AdabB).'">'.$r->AdabB.'</span>' : '<span class="dash-empty">—</span>' !!}</td>
                                <td class="col-grade">{!! $r->AdabC ? '<span class="grade-badge grade-'.strtolower($r->AdabC).'">'.$r->AdabC.'</span>' : '<span class="dash-empty">—</span>' !!}</td>
                                <td class="col-grade">{!! $r->AdabD ? '<span class="grade-badge grade-'.strtolower($r->AdabD).'">'.$r->AdabD.'</span>' : '<span class="dash-empty">—</span>' !!}</td>
                                <td class="col-grade">{!! $r->AdabE ? '<span class="grade-badge grade-'.strtolower($r->AdabE).'">'.$r->AdabE.'</span>' : '<span class="dash-empty">—</span>' !!}</td>

                                <td class="col-grade">{!! $r->KepribadianA ? '<span class="grade-badge grade-'.strtolower($r->KepribadianA).'">'.$r->KepribadianA.'</span>' : '<span class="dash-empty">—</span>' !!}</td>
                                <td class="col-grade">{!! $r->KepribadianB ? '<span class="grade-badge grade-'.strtolower($r->KepribadianB).'">'.$r->KepribadianB.'</span>' : '<span class="dash-empty">—</span>' !!}</td>
                                <td class="col-grade">{!! $r->KepribadianC ? '<span class="grade-badge grade-'.strtolower($r->KepribadianC).'">'.$r->KepribadianC.'</span>' : '<span class="dash-empty">—</span>' !!}</td>
                                <td class="col-grade">{!! $r->KepribadianD ? '<span class="grade-badge grade-'.strtolower($r->KepribadianD).'">'.$r->KepribadianD.'</span>' : '<span class="dash-empty">—</span>' !!}</td>
                                <td class="col-grade">{!! $r->KepribadianE ? '<span class="grade-badge grade-'.strtolower($r->KepribadianE).'">'.$r->KepribadianE.'</span>' : '<span class="dash-empty">—</span>' !!}</td>

                                <td class="col-diri" title="{{ $r->PengembanganA }}">{{ $r->PengembanganA ?: '—' }}</td>
                                <td class="col-diri" title="{{ $r->PengembanganB }}">{{ $r->PengembanganB ?: '—' }}</td>
                                <td class="col-diri" title="{{ $r->PengembanganC }}">{{ $r->PengembanganC ?: '—' }}</td>

                                <td class="col-catatan" title="{{ $r->Catatan }}">{{ $r->Catatan ?: '—' }}</td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="22" class="text-center empty-state-box">
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
