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
            <h2>{{ $selectedUnit === 'belum' ? 'Belum diisi' : $selectedUnit }}</h2>
            <div class="panel-block-meta">
                <button type="button" class="text-button" data-open="add-pelanggaran">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Tambah
                </button>
                <a href="{{ route('kesantrian.master-pelanggaran.template') }}" class="ghost" style="height: 36px; padding: 0 12px; font-size: 12.5px;">Template Excel</a>
                <button type="button" class="text-button" data-open="upload-pelanggaran">Unggah Excel</button>
                <span class="pill-item">
                    <span class="pill-label">Total:</span>
                    <span class="pill-value is-highlight">{{ $items->count() }} data</span>
                </span>
            </div>
        </div>

        @if ($lockedUnit === null)
            <div class="unit-tabs">
                @foreach ($units as $unit)
                    <a href="{{ route('kesantrian.menu', ['menu' => 'master-pelanggaran', 'unit' => $unit]) }}" class="{{ $selectedUnit === $unit ? 'is-active' : '' }}">{{ $unit }}</a>
                @endforeach
                @if ($unassigned > 0)
                    <a href="{{ route('kesantrian.menu', ['menu' => 'master-pelanggaran', 'unit' => 'belum']) }}" class="{{ $selectedUnit === 'belum' ? 'is-active' : '' }}">Belum diisi ({{ $unassigned }})</a>
                @endif
            </div>
        @endif

        <div class="table-wrap">
            <table class="data-table is-refs">
                <thead>
                    <tr>
                        <th class="col-no">No</th>
                        <th>Unit</th>
                        <th>Nama pelanggaran</th>
                        <th class="col-score">Skor</th>
                        <th class="col-note">Keterangan</th>
                        <th class="col-action">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr>
                            <td class="col-no">{{ $item->urut }}</td>
                            <td>{{ $item->unit }}</td>
                            <td style="font-weight: 500;">{{ $item->jenis_pelangaran }}</td>
                            <td class="col-score">{{ $item->skor }}</td>
                            <td class="col-note">{{ $item->keterangan }}</td>
                            <td class="col-action">
                                <div class="row-actions">
                                    <button
                                        type="button"
                                        class="text-button"
                                        data-edit-pelanggaran
                                        data-urut="{{ $item->urut }}"
                                        data-unit="{{ $item->unit }}"
                                        data-nama="{{ $item->jenis_pelangaran }}"
                                        data-skor="{{ $item->skor }}"
                                        data-keterangan="{{ $item->keterangan }}"
                                    >Ubah</button>
                                    <form method="POST" action="{{ route('kesantrian.master-pelanggaran.destroy', $item->urut) }}" onsubmit="return confirm('Hapus pelanggaran ini? Data lama tetap tersimpan.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-row-delete">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center empty-state-box">
                                <p style="margin: 8px 0; color: var(--ink-soft); font-size: 14px;">Belum ada pelanggaran pada unit ini.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <dialog class="modal" id="add-pelanggaran" @if ($errors->any() && old('_method') !== 'PUT') data-show @endif>
        <form class="modal-form" method="POST" action="{{ route('kesantrian.master-pelanggaran.store') }}">
            @csrf
            <div class="modal-head">
                <h2>Tambah pelanggaran</h2>
                <button type="button" class="btn-modal-close" data-close aria-label="Tutup">&times;</button>
            </div>
            @include('kesantrian.partials.pelanggaran-fields', ['selectedUnit' => $selectedUnit === 'belum' ? old('unit') : $selectedUnit])
        </form>
    </dialog>

    <dialog class="modal" id="edit-pelanggaran" @if ($errors->any() && old('_method') === 'PUT') data-show @endif>
        <form class="modal-form" id="form-edit-pelanggaran" method="POST" action="{{ route('kesantrian.master-pelanggaran.update', old('urut', 0)) }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="urut" value="{{ old('urut') }}">
            <div class="modal-head">
                <h2>Ubah pelanggaran</h2>
                <button type="button" class="btn-modal-close" data-close aria-label="Tutup">&times;</button>
            </div>
            @include('kesantrian.partials.pelanggaran-fields', ['selectedUnit' => old('unit', $selectedUnit === 'belum' ? '' : $selectedUnit)])
        </form>
    </dialog>

    <dialog class="modal" id="upload-pelanggaran">
        <form class="modal-form" method="POST" action="{{ route('kesantrian.master-pelanggaran.import') }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-head">
                <h2>Unggah Excel</h2>
                <button type="button" class="btn-modal-close" data-close aria-label="Tutup">&times;</button>
            </div>
            <div style="display: grid; gap: 14px; margin-top: 14px;">
                <label>
                    Berkas
                    <input type="file" name="berkas" accept=".xlsx,.csv,.txt" required>
                    <small class="field-note">Kolom: Unit, Nama pelanggaran, Skor, Keterangan. Unit yang dipakai: KUTTAB, KMI, SMP, SMA, dan MA'HAD ALY. Nama yang sama pada unit yang sama akan diperbarui. Paling banyak 1000 baris.</small>
                </label>
            </div>
            <div class="modal-actions" style="margin-top: 20px; padding: 14px 0 0;">
                <button type="button" class="ghost" data-close>Batal</button>
                <button type="submit" class="btn-primary">Unggah</button>
            </div>
        </form>
    </dialog>

    <script>
        document.querySelectorAll('[data-edit-pelanggaran]').forEach(function (button) {
            button.addEventListener('click', function () {
                var form = document.getElementById('form-edit-pelanggaran');
                var dialog = document.getElementById('edit-pelanggaran');
                form.action = @json(url('/kesantrian/master-pelanggaran')).replace(/\/$/, '') + '/' + button.getAttribute('data-urut');
                form.querySelector('[name="urut"]').value = button.getAttribute('data-urut');
                form.querySelector('[name="nama"]').value = button.getAttribute('data-nama') || '';
                form.querySelector('[name="skor"]').value = button.getAttribute('data-skor') || '';
                form.querySelector('[name="keterangan"]').value = button.getAttribute('data-keterangan') || '';
                var unit = form.querySelector('[name="unit"]');
                if (unit) {
                    unit.value = button.getAttribute('data-unit') || '';
                }
                if (dialog && typeof dialog.showModal === 'function') {
                    dialog.showModal();
                }
            });
        });
    </script>
@endsection
