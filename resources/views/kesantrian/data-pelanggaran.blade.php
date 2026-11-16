@extends('kesantrian.partials.shell')

@section('title', 'Data Pelanggaran Siswa — Kesantrian')

@section('desk')
    @include('kesantrian.partials.page-head', [
        'kicker' => $current['group'],
        'heading' => 'Data Pelanggaran Siswa',
        'summary' => $current['summary'],
    ])

    <section class="panel-block">
        <div class="panel-block-head">
            <h2>Filter Data Pelanggaran Siswa</h2>
        </div>
        <form class="filter-sheet" method="GET" action="{{ route('kesantrian.menu', 'data-pelanggaran') }}">
            @include('partials.keep-grup')
            <label>
                Tahun Akademik
                <select name="thn_aka">
                    <option value="">Semua Tahun Akademik</option>
                    @foreach ($years as $year)
                        <option value="{{ $year->thn_aka }}" @selected($selectedYear === $year->thn_aka)>{{ $year->thn_aka }}</option>
                    @endforeach
                </select>
            </label>

            <label>
                Kelas
                <select name="kelas">
                    <option value="">Semua Kelas</option>
                    @foreach ($classes as $namaKelas)
                        <option value="{{ $namaKelas }}" @selected($selectedClass === $namaKelas)>{{ $namaKelas }}</option>
                    @endforeach
                </select>
            </label>

            <label>
                NIS / Nama Siswa
                <input name="q" value="{{ $search }}" placeholder="Ketik NIS atau nama santri..." autocomplete="off">
            </label>

            <div class="filter-actions">
                <a href="{{ route('kesantrian.menu', 'data-pelanggaran') }}" class="ghost" title="Reset filter">
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
            <h2>Data Rekap Pelanggaran</h2>
            <div class="panel-block-meta">
                <span class="pill-item">
                    <span class="pill-label">Total Data:</span>
                    <span class="pill-value is-highlight">{{ $records->total() }} santri</span>
                </span>
            </div>
        </div>

        @include('partials.group-status')
        <div class="table-wrap has-complex-head">
            <table class="data-table is-summary" id="tabel-data-pelanggaran">
                <thead>
                    <tr>
                        <x-group-column label="NIS" column-key="NOCUST" :group-key="$groupKey" class="col-nis" rowspan="2" />
                        <x-group-column label="Nama Siswa" column-key="NMCUST" :group-key="$groupKey" class="col-name" rowspan="2" />
                        <x-group-column label="Kelas" column-key="kelas" :group-key="$groupKey" class="col-class" rowspan="2" />
                        <th colspan="2" class="col-score-head">Skor</th>
                        <x-group-column label="Total" column-key="total_skor" :group-key="$groupKey" class="col-total" rowspan="2" />
                        <x-group-column label="Thn Akademik" column-key="thn_aka" :group-key="$groupKey" class="col-thn" rowspan="2" />
                        <th rowspan="2" class="col-action">Aksi</th>
                    </tr>
                    <tr>
                        <x-group-column label="Pelanggaran" column-key="total_pelanggaran" :group-key="$groupKey" class="col-score" />
                        <x-group-column label="Remisi" column-key="total_remisi" :group-key="$groupKey" class="col-score" />
                    </tr>
                </thead>
                <tbody>
                    @php $groupBand = null; @endphp
                    @forelse ($records as $row)
                        @if ($groupKey)
                            @php $groupValue = \App\Tables\ColumnGroup::text($row, $groupKey); @endphp
                            @if ($groupBand !== $groupValue)
                                <x-group-band :label="$groupLabel" :value="$groupValue" span="8" />
                                @php $groupBand = $groupValue; @endphp
                            @endif
                        @endif
                        <tr>
                            <td class="col-nis">{{ trim((string) $row->NOCUST) }}</td>
                            <td class="col-name">{{ trim((string) $row->NMCUST) ?: '-' }}</td>
                            <td class="col-class">{{ trim((string) $row->kelas) ?: '-' }}</td>
                            <td class="col-score">{{ $row->total_pelanggaran }}</td>
                            <td class="col-score is-debet">{{ $row->total_remisi }}</td>
                            <td class="col-total">{{ $row->total_skor }}</td>
                            <td class="col-thn">{{ $row->thn_aka }}</td>
                            <td class="col-action">
                                <button type="button" class="btn-detail" data-nis="{{ trim((string) $row->NOCUST) }}" data-thn="{{ $row->thn_aka }}" title="Lihat rincian riwayat pelanggaran santri">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                    Detail
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center empty-state-box">
                                <p style="margin: 8px 0; color: var(--ink-soft); font-size: 14px;">
                                    Tidak ada data pelanggaran yang cocok dengan kriteria filter.
                                </p>
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

    <dialog class="modal is-large" id="modal-detail-santri">
        <div class="modal-form">
            <div class="modal-head">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--brand);">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                    <h2>Rincian Catatan Santri</h2>
                </div>
                <button type="button" class="btn-modal-close" data-close aria-label="Tutup">&times;</button>
            </div>

            <div class="student-meta-box" id="detail-student-meta">
                <div class="meta-row">
                    <div>
                        <span class="meta-label">Nama Siswa:</span>
                        <strong id="modal-nama">-</strong>
                    </div>
                    <div>
                        <span class="meta-label">NIS:</span>
                        <span id="modal-nis">-</span>
                    </div>
                    <div>
                        <span class="meta-label">Kelas:</span>
                        <span id="modal-kelas">-</span>
                    </div>
                    <div>
                        <span class="meta-label">Tahun Akademik:</span>
                        <span id="modal-thn">-</span>
                    </div>
                </div>

                <div class="summary-pills">
                    <div class="pill-item">
                        <span class="pill-label">Total Pelanggaran:</span>
                        <strong class="pill-value is-danger" id="modal-total-pelanggaran">0</strong>
                    </div>
                    <div class="pill-item">
                        <span class="pill-label">Total Remisi:</span>
                        <strong class="pill-value is-success" id="modal-total-remisi">0</strong>
                    </div>
                    <div class="pill-item">
                        <span class="pill-label">Total Skor Berjalan:</span>
                        <strong class="pill-value is-highlight" id="modal-total-skor">0</strong>
                    </div>
                </div>
            </div>

            <div class="table-wrap modal-table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="col-date">Tanggal</th>
                            <th class="col-thn">Tahun</th>
                            <th>Pelanggaran / Remisi</th>
                            <th class="col-score">Skor Pelanggaran</th>
                            <th class="col-score">Skor Remisi</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody id="modal-tbody">
                        <tr>
                            <td colspan="6" class="text-center">Memuat rincian data...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="modal-actions">
                <button type="button" class="ghost" data-close>Tutup</button>
            </div>
        </div>
    </dialog>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var modal = document.getElementById('modal-detail-santri');
            var modalNama = document.getElementById('modal-nama');
            var modalNis = document.getElementById('modal-nis');
            var modalKelas = document.getElementById('modal-kelas');
            var modalThn = document.getElementById('modal-thn');
            var modalPelanggaran = document.getElementById('modal-total-pelanggaran');
            var modalRemisi = document.getElementById('modal-total-remisi');
            var modalTotal = document.getElementById('modal-total-skor');
            var modalTbody = document.getElementById('modal-tbody');
            var escapeHtml = function (str) {
                if (str === null || str === undefined) return '';
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');
            };

            document.querySelectorAll('.btn-detail').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var nis = btn.getAttribute('data-nis');
                    var thn = btn.getAttribute('data-thn');

                    if (window.portalProgress) {
                        window.portalProgress.start();
                    }

                    fetch(@json(route('kesantrian.data-pelanggaran.detail')).concat('?nis=').concat(encodeURIComponent(nis)).concat('&thn=').concat(encodeURIComponent(thn)), {
                        headers: { 'Accept': 'application/json' }
                    }).then(function (response) {
                        if (!response.ok) {
                            throw new Error('Gagal mengambil data detail');
                        }
                        return response.json();
                    }).then(function (data) {
                        modalNama.textContent = data.nama || '-';
                        modalNis.textContent = data.nis || '-';
                        modalKelas.textContent = data.kelas || '-';
                        modalThn.textContent = data.thn_aka || 'Semua';
                        modalPelanggaran.textContent = data.total_pelanggaran;
                        modalRemisi.textContent = data.total_remisi;
                        modalTotal.textContent = data.total_skor;

                        modalTbody.innerHTML = '';
                        if (!data.items || data.items.length === 0) {
                            modalTbody.innerHTML = '<tr><td colspan="6" class="text-center">Tidak ada riwayat catatan untuk santri ini.</td></tr>';
                        } else {
                            data.items.forEach(function (item) {
                                var tr = document.createElement('tr');
                                tr.innerHTML = '<td class="col-date">' + escapeHtml(item.tanggal) + '</td>' +
                                    '<td class="col-thn">' + escapeHtml(item.thn_aka) + '</td>' +
                                    '<td>' + escapeHtml(item.pelanggaran) + '</td>' +
                                    '<td class="col-score">' + escapeHtml(item.skor_kredit) + '</td>' +
                                    '<td class="col-score is-debet">' + escapeHtml(item.skor_debet) + '</td>' +
                                    '<td>' + escapeHtml(item.keterangan) + '</td>';
                                modalTbody.appendChild(tr);
                            });
                        }

                        if (modal && typeof modal.showModal === 'function') {
                            modal.showModal();
                        }
                    }).catch(function (err) {
                        alert('Gagal memuat detail data pelanggaran.');
                    }).finally(function () {
                        if (window.portalProgress) {
                            window.portalProgress.stop();
                        }
                    });
                });
            });
        });
    </script>
@endsection
