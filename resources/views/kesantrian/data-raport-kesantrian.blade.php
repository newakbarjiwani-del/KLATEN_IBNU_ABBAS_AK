@extends('kesantrian.partials.shell')

@section('title', 'Data Raport Kesantrian — Kesantrian')

@section('desk')
    @include('kesantrian.partials.page-head', [
        'kicker' => $current['group'],
        'heading' => 'Data Raport Kesantrian',
        'summary' => 'Lihat, telusuri, dan kelola arsip penilaian raport kesantrian santri.',
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

    @include('kesantrian.partials.unit-tabs', ['menu' => 'data-raport-kesantrian'])

    @php
        $punyaButir = $kategori->contains(fn ($kelompok) => $kelompok->items->isNotEmpty());
        $jumlahButir = $kategori->sum(fn ($kelompok) => $kelompok->items->count());
        $jumlahKolom = 9 + $jumlahButir;
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
            <h2>Filter Data Raport Kesantrian</h2>
            <div class="panel-block-meta">
                <a href="{{ route('kesantrian.menu', 'input-raport-kesantrian') }}" class="btn-import-open" style="height: 36px; padding: 0 14px; text-decoration: none; font-size: 13px;" title="Import & input raport baru">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 6px;">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="17 8 12 3 7 8"></polyline>
                        <line x1="12" y1="3" x2="12" y2="15"></line>
                    </svg>
                    Import Raport Baru
                </a>
            </div>
        </div>

        <form class="raport-filter-grid" method="GET" action="{{ route('kesantrian.menu', 'data-raport-kesantrian') }}">
            @include('partials.keep-grup')
            <input type="hidden" name="unit" value="{{ $selectedUnit }}">
            <div class="filter-field">
                <label>
                    Tahun akademik
                    <select name="periode">
                        <option value="">Semua tahun</option>
                        @foreach ($years as $year)
                            <option value="{{ $year->thn_aka }}" @selected($selectedPeriode === $year->thn_aka)>
                                {{ $year->thn_aka }}
                            </option>
                        @endforeach
                    </select>
                </label>
            </div>

            <div class="filter-field">
                <label>
                    Tanggal Mulai
                    <input type="date" name="tgl_awal" value="{{ $tglAwal }}">
                </label>
            </div>

            <div class="filter-field">
                <label>
                    Tanggal Akhir
                    <input type="date" name="tgl_akhir" value="{{ $tglAkhir }}">
                </label>
            </div>

            <div class="filter-field is-search">
                <label>
                    NIS / Nama Santri
                    <input name="q" value="{{ $search }}" placeholder="Ketik NIS atau nama santri..." autocomplete="off">
                </label>
            </div>

            <div class="filter-actions-row">
                <a href="{{ route('kesantrian.menu', 'data-raport-kesantrian') }}" class="ghost" title="Reset filter pencarian">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;">
                        <polyline points="1 4 1 10 7 10"></polyline>
                        <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                    </svg>
                    Reset
                </a>
                <button type="submit" class="btn-cari">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 6px;">
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
            <h2>Daftar Nilai Raport Kesantrian</h2>
            <div class="panel-block-meta">
                <span class="pill-item">
                    <span class="pill-label">Total Data:</span>
                    <span class="pill-value is-highlight">{{ $records->total() }}</span>
                </span>
                @include('partials.excel-link', ['href' => route('kesantrian.data-raport-kesantrian.export', array_merge(request()->query(), ['unit' => $selectedUnit]))])
                <button type="button" class="btn-bulk-delete" id="btn-hapus-terpilih" disabled title="Pilih baris pada tabel untuk menghapus">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 6px;">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                    Hapus Terpilih
                </button>
            </div>
        </div>

        @include('partials.group-status')
        <form id="form-bulk-delete" method="POST" action="{{ route('kesantrian.data-raport-kesantrian.bulk') }}">
            @csrf
            @method('DELETE')
            <input type="hidden" name="unit" value="{{ $selectedUnit }}">

            <div class="table-wrap has-complex-head">
                <table class="data-table is-raport" id="tabel-data-raport">
                    <thead>
                        <tr>
                            <th rowspan="2" class="col-check">
                                <input type="checkbox" id="check-all" title="Pilih Semua">
                            </th>
                            <th rowspan="2" class="col-num">No</th>
                            <x-group-column label="Tahun akademik" column-key="periode" :group-key="$groupKey" class="col-periode" rowspan="2" />
                            <x-group-column label="NIS" column-key="NOCUST" :group-key="$groupKey" class="col-nis" rowspan="2" />
                            <x-group-column label="Nama Siswa" column-key="NMCUST" :group-key="$groupKey" class="col-name" rowspan="2" />
                            <x-group-column label="Kelompok" column-key="kelompok" :group-key="$groupKey" rowspan="2" />
                            <x-group-column label="Tanggal" column-key="tgl" :group-key="$groupKey" class="col-date" rowspan="2" />
                            @foreach ($kategori as $index => $kelompok)
                                @continue($kelompok->items->isEmpty())
                                @php $gaya = ['group-ibadah', 'group-adab', 'group-pribadi', 'group-diri'][$index % 4]; @endphp
                                <th colspan="{{ $kelompok->items->count() }}" class="col-group-head {{ $gaya }}">{{ $kelompok->nama }}</th>
                            @endforeach
                            <th class="col-group-head group-catatan">Catatan</th>
                            <th rowspan="2" class="col-action">Aksi</th>
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
                        @php $groupBand = null; @endphp
                        @forelse ($records as $index => $row)
                            @if ($groupKey)
                                @php $groupValue = \App\Tables\ColumnGroup::text($row, $groupKey); @endphp
                                @if ($groupBand !== $groupValue)
                                    <x-group-band :label="$groupLabel" :value="$groupValue" :span="$jumlahKolom" />
                                    @php $groupBand = $groupValue; @endphp
                                @endif
                            @endif
                            <tr>
                                <td class="col-check">
                                    <input type="checkbox" name="ids[]" value="{{ $row->urut }}" class="row-checkbox">
                                </td>
                                <td class="col-num">{{ $records->firstItem() + $index }}</td>
                                <td class="col-periode">{{ $row->periode }}</td>
                                <td class="col-nis">{{ trim((string) $row->NOCUST) }}</td>
                                <td class="col-name">{{ trim((string) $row->NMCUST) ?: '-' }}</td>
                                <td>{{ trim((string) $row->kelompok) }}</td>
                                <td class="col-date">{{ $row->tgl }}</td>
                                @foreach ($kategori as $kelompok)
                                    @foreach ($kelompok->items as $item)
                                        @include('kesantrian.partials.raport-nilai', ['nilai' => $row->nilai[$item->id] ?? ''])
                                    @endforeach
                                @endforeach
                                <td class="col-catatan" title="{{ $row->Catatan }}">{{ $row->Catatan ?: '—' }}</td>

                                <td class="col-action">
                                    <div class="row-actions">
                                    <a class="btn-unduh" href="{{ route('kesantrian.data-raport-kesantrian.unduh', ['urut' => $row->urut, 'unit' => $selectedUnit]) }}">Unduh</a>
                                    <button type="button" class="btn-row-delete" data-urut="{{ $row->urut }}" data-nama="{{ trim((string) $row->NMCUST) ?: trim((string) $row->NOCUST) }}" data-periode="{{ $row->periode }}" title="Hapus baris ini">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                        </svg>
                                        Hapus
                                    </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $jumlahKolom }}" class="text-center empty-state-box">
                                    <p style="margin: 8px 0; color: var(--ink-soft); font-size: 14px;">
                                        Tidak ada data raport kesantrian yang sesuai dengan kriteria filter.
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </form>

        @if ($records->hasPages())
            <div class="pagination-wrap">
                {{ $records->links('kesantrian.partials.pagination') }}
            </div>
        @endif
    </section>

    <!-- Modal Konfirmasi Hapus Single -->
    <dialog class="modal is-compact" id="modal-delete-single">
        <form id="form-delete-single" method="POST" action="">
            @csrf
            @method('DELETE')
            <input type="hidden" name="unit" value="{{ $selectedUnit }}">
            <div class="modal-head is-danger-head">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <div class="modal-icon-danger">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                    </div>
                    <h3>Konfirmasi Hapus Raport</h3>
                </div>
                <button type="button" class="btn-modal-close" data-modal="modal-delete-single" aria-label="Tutup modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus data penilaian raport kesantrian untuk santri <strong id="delete-student-name"></strong> pada periode <strong id="delete-student-periode"></strong>?</p>
                <div class="modal-danger-callout">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex: none;">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                    <span>Tindakan ini permanen dan data yang dihapus tidak dapat dipulihkan kembali.</span>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="ghost" data-modal="modal-delete-single">Batal</button>
                <button type="submit" class="btn-danger-confirm">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 6px;">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                    Ya, Hapus Data
                </button>
            </div>
        </form>
    </dialog>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var checkAll = document.getElementById('check-all');
            var rowCheckboxes = document.querySelectorAll('.row-checkbox');
            var btnBulkDelete = document.getElementById('btn-hapus-terpilih');
            var formBulkDelete = document.getElementById('form-bulk-delete');

            function updateBulkDeleteState() {
                var checkedCount = 0;
                rowCheckboxes.forEach(function (cb) {
                    var tr = cb.closest('tr');
                    if (cb.checked) {
                        checkedCount++;
                        if (tr) tr.classList.add('is-selected');
                    } else {
                        if (tr) tr.classList.remove('is-selected');
                    }
                });
                if (btnBulkDelete) {
                    btnBulkDelete.disabled = checkedCount === 0;
                    btnBulkDelete.innerHTML = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 6px;"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>' + (checkedCount > 0 ? 'Hapus Terpilih (' + checkedCount + ')' : 'Hapus Terpilih');
                }
            }

            if (checkAll) {
                checkAll.addEventListener('change', function () {
                    var isChecked = this.checked;
                    rowCheckboxes.forEach(function (cb) {
                        cb.checked = isChecked;
                    });
                    updateBulkDeleteState();
                });
            }

            rowCheckboxes.forEach(function (cb) {
                cb.addEventListener('change', function () {
                    if (!this.checked && checkAll) {
                        checkAll.checked = false;
                    }
                    updateBulkDeleteState();
                });
            });

            if (btnBulkDelete && formBulkDelete) {
                btnBulkDelete.addEventListener('click', function () {
                    var checkedCount = document.querySelectorAll('.row-checkbox:checked').length;
                    if (checkedCount === 0) return;

                    if (confirm('Apakah Anda yakin ingin menghapus ' + checkedCount + ' data raport kesantrian terpilih? Tindakan ini tidak dapat dibatalkan.')) {
                        formBulkDelete.submit();
                    }
                });
            }

            // Single row delete dialog
            var modalDelete = document.getElementById('modal-delete-single');
            var formDelete = document.getElementById('form-delete-single');
            var nameSpan = document.getElementById('delete-student-name');
            var periodeSpan = document.getElementById('delete-student-periode');

            document.querySelectorAll('.btn-row-delete').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var urut = this.getAttribute('data-urut');
                    var nama = this.getAttribute('data-nama');
                    var periode = this.getAttribute('data-periode');

                    if (formDelete) {
                        formDelete.action = '{{ url("kesantrian/data-raport-kesantrian") }}/' + urut;
                    }
                    if (nameSpan) nameSpan.textContent = nama;
                    if (periodeSpan) periodeSpan.textContent = periode;

                    if (modalDelete && typeof modalDelete.showModal === 'function') {
                        modalDelete.showModal();
                    } else if (modalDelete) {
                        modalDelete.setAttribute('open', '');
                    }
                });
            });

            document.querySelectorAll('[data-modal]').forEach(function (el) {
                el.addEventListener('click', function () {
                    var targetId = this.getAttribute('data-modal');
                    var modal = document.getElementById(targetId);
                    if (modal && typeof modal.close === 'function') {
                        modal.close();
                    } else if (modal) {
                        modal.removeAttribute('open');
                    }
                });
            });
        });
    </script>
@endsection
