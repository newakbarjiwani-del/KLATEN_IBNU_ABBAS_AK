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
            <h2>Input</h2>
        </div>
        <form class="sheet" id="form-pelanggaran" method="POST" action="{{ route('kesantrian.input-pelanggaran.store') }}">
            @csrf
            <label>
                Jenis
                <select name="jenis" required>
                    <option value="pelanggaran" @selected(old('jenis', 'pelanggaran') === 'pelanggaran')>Pelanggaran</option>
                    <option value="remisi" @selected(old('jenis') === 'remisi')>Remisi</option>
                </select>
            </label>
            <label>
                Tahun akademik
                <select name="thn_aka" required>
                    <option value="">Pilih tahun</option>
                    @foreach ($years as $year)
                        <option value="{{ $year->thn_aka }}" @selected(old('thn_aka') === $year->thn_aka)>{{ $year->thn_aka }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                Tanggal
                <input type="date" name="tgl_kejadian" value="{{ old('tgl_kejadian', now()->toDateString()) }}" required>
            </label>
            <label>
                NIS
                <span class="nis-lookup">
                    <input name="nocust" value="{{ old('nocust', $selectedNis) }}" maxlength="10" autocomplete="off" required data-nis-suggest="{{ route('kesantrian.santri.suggest') }}" data-nis-target="nama-santri">
                    <div class="nis-menu" hidden></div>
                </span>
            </label>
            <label>
                Nama
                <input id="nama-santri" value="{{ $selectedSantri?->nama() ?? '' }}" readonly>
            </label>
            <label>
                Pelanggaran
                <select name="id_langgar" required>
                    <option value="">Pilih pelanggaran</option>
                    @foreach ($references as $reference)
                        <option value="{{ $reference->urut }}" data-skor="{{ $reference->skor }}" @selected((string) old('id_langgar') === (string) $reference->urut)>{{ $reference->jenis_pelangaran }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                Skor
                <input name="skor" type="number" min="0" max="32767" value="{{ old('skor') }}" inputmode="numeric">
            </label>
            <label class="is-wide">
                Keterangan
                <textarea name="keterangan" rows="2" maxlength="1000">{{ old('keterangan') }}</textarea>
            </label>
            <div class="sheet-actions">
                <button type="reset" class="ghost">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;">
                        <polyline points="1 4 1 10 7 10"></polyline>
                        <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                    </svg>
                    Reset
                </button>
                <button type="submit" class="btn-primary">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    Simpan
                </button>
            </div>
        </form>
    </section>

    <section class="panel-block">
        <div class="panel-block-head">
            <h2 id="riwayat-heading">
                @if ($selectedSantri)
                    Riwayat: {{ $selectedSantri->nama() }} ({{ $selectedNis }})
                @elseif ($selectedNis)
                    Riwayat: NIS {{ $selectedNis }}
                @else
                    Riwayat Pelanggaran
                @endif
            </h2>
            <div class="panel-block-meta">
                <span id="riwayat-count">{{ $records->count() }} data</span>
                <a href="{{ route('kesantrian.menu', 'data-pelanggaran') }}" class="view-all-link">Data Pelanggaran Lengkap &rarr;</a>
            </div>
        </div>

        <div class="summary-pills" id="riwayat-pills" style="{{ $selectedNis ? '' : 'display: none;' }}">
            <div class="pill-item">
                <span class="pill-label">Total Pelanggaran</span>
                <strong class="pill-value is-danger" id="pill-pelanggaran">{{ $totals['pelanggaran'] }}</strong>
            </div>
            <div class="pill-item">
                <span class="pill-label">Total Remisi</span>
                <strong class="pill-value is-success" id="pill-remisi">{{ $totals['remisi'] }}</strong>
            </div>
            <div class="pill-item">
                <span class="pill-label">Total Skor Berjalan</span>
                <strong class="pill-value is-highlight" id="pill-total">{{ $totals['sisa'] }}</strong>
            </div>
        </div>

        <div class="table-wrap">
            <table class="data-table is-history" id="riwayat-pelanggaran">
                <thead>
                    <tr>
                        <th class="col-nis">NIS</th>
                        <th class="col-name">Nama</th>
                        <th class="col-date">Tanggal</th>
                        <th class="col-thn">Tahun akademik</th>
                        <th class="col-desc">Pelanggaran</th>
                        <th class="col-score">Skor pelanggaran</th>
                        <th class="col-score">Skor remisi</th>
                        <th class="col-note">Keterangan</th>
                    </tr>
                </thead>
                <tbody id="riwayat-tbody">
                    @forelse ($records as $record)
                        <tr data-nis="{{ trim((string) $record->NOCUST) }}">
                            <td class="col-nis">{{ trim((string) $record->NOCUST) }}</td>
                            <td class="col-name">{{ trim((string) $record->NMCUST) }}</td>
                            <td class="col-date">{{ $record->tgl_kejadian ? \Illuminate\Support\Carbon::parse($record->tgl_kejadian)->format('d/m/Y') : '' }}</td>
                            <td class="col-thn">{{ $record->thn_aka }}</td>
                            <td class="col-desc">{{ $record->jenis_pelangaran }}</td>
                            <td class="col-score">{{ $record->id_pelanggaran !== null ? $record->skor_kredit : '' }}</td>
                            <td class="col-score">{{ $record->id_remisi !== null ? $record->skor_debet : '' }}</td>
                            <td class="col-note">{{ $record->keterangan }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">Belum ada catatan pelanggaran untuk santri ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var form = document.getElementById('form-pelanggaran');
            var jenis = form.querySelector('[name="jenis"]');
            var pelanggaran = form.querySelector('[name="id_langgar"]');
            var skor = form.querySelector('[name="skor"]');
            var nis = form.querySelector('[name="nocust"]');
            var nama = document.getElementById('nama-santri');
            var tbody = document.getElementById('riwayat-tbody');
            var heading = document.getElementById('riwayat-heading');
            var count = document.getElementById('riwayat-count');
            var pills = document.getElementById('riwayat-pills');
            var pillPelanggaran = document.getElementById('pill-pelanggaran');
            var pillRemisi = document.getElementById('pill-remisi');
            var pillTotal = document.getElementById('pill-total');

            var syncScore = function () {
                var option = pelanggaran.options[pelanggaran.selectedIndex];
                var master = option ? option.getAttribute('data-skor') : '';
                var violation = jenis.value === 'pelanggaran';

                skor.readOnly = violation;
                if (violation) {
                    skor.value = master || '';
                }
            };

            var renderRows = function (items, studentNis, studentName) {
                tbody.innerHTML = '';
                if (!items || items.length === 0) {
                    var emptyTr = document.createElement('tr');
                    emptyTr.innerHTML = '<td colspan="8" class="text-center">Belum ada catatan pelanggaran untuk santri ini.</td>';
                    tbody.appendChild(emptyTr);
                    return;
                }

                items.forEach(function (rec) {
                    var tr = document.createElement('tr');
                    tr.setAttribute('data-nis', rec.nocust);
                    tr.innerHTML = '<td class="col-nis">' + escapeHtml(rec.nocust) + '</td>' +
                        '<td class="col-name">' + escapeHtml(rec.nmcust) + '</td>' +
                        '<td class="col-date">' + escapeHtml(rec.tanggal) + '</td>' +
                        '<td class="col-thn">' + escapeHtml(rec.thn_aka) + '</td>' +
                        '<td class="col-desc">' + escapeHtml(rec.jenis_pelangaran) + '</td>' +
                        '<td class="col-score">' + escapeHtml(rec.skor_kredit) + '</td>' +
                        '<td class="col-score">' + escapeHtml(rec.skor_debet) + '</td>' +
                        '<td class="col-note">' + escapeHtml(rec.keterangan) + '</td>';
                    tbody.appendChild(tr);
                });
            };

            var escapeHtml = function (str) {
                if (str === null || str === undefined) return '';
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');
            };

            jenis.addEventListener('change', syncScore);
            pelanggaran.addEventListener('change', syncScore);
            form.addEventListener('reset', function () {
                window.setTimeout(syncScore, 0);
            });
            syncScore();

            var loadSantri = function (value) {
                if (value === '') {
                    return;
                }

                if (window.portalProgress) {
                    window.portalProgress.start();
                }

                fetch(@json(route('kesantrian.input-pelanggaran.santri')).concat('?nis=').concat(encodeURIComponent(value)), {
                    headers: { 'Accept': 'application/json' }
                }).then(function (response) {
                    if (!response.ok) {
                        throw new Error('missing');
                    }
                    return response.json();
                }).then(function (data) {
                    nama.value = data.nama || '';
                    heading.textContent = 'Riwayat: ' + data.nama + ' (' + data.nis + ')';
                    count.textContent = (data.records ? data.records.length : 0) + ' data';
                    pills.style.display = 'flex';
                    pillPelanggaran.textContent = data.totals.pelanggaran;
                    pillRemisi.textContent = data.totals.remisi;
                    pillTotal.textContent = data.totals.sisa;
                    renderRows(data.records, data.nis, data.nama);
                }).catch(function () {
                    nama.value = 'Tidak ditemukan';
                    heading.textContent = 'Riwayat: NIS ' + value;
                    count.textContent = '0 data';
                    pills.style.display = 'none';
                    renderRows([], value, '');
                }).finally(function () {
                    if (window.portalProgress) {
                        window.portalProgress.stop();
                    }
                });
            };

            nis.addEventListener('nis-picked', function (event) {
                loadSantri(event.detail.nis);
            });
        });
    </script>
@endsection
