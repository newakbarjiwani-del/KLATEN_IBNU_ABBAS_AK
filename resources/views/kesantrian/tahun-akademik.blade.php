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
            <h2>Daftar</h2>
            <div class="panel-block-meta">
                <button type="button" class="text-button" data-open="add-tahun">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Tambah
                </button>
                <span class="pill-item">
                    <span class="pill-label">Total:</span>
                    <span class="pill-value is-highlight">{{ $years->count() }} data</span>
                </span>
            </div>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="col-no">No</th>
                        <th>Tahun akademik</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($years as $year)
                        <tr>
                            <td class="col-no">{{ $year->urut }}</td>
                            <td style="font-weight: 600;">{{ $year->thn_aka }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="text-center empty-state-box">
                                <p style="margin: 8px 0; color: var(--ink-soft); font-size: 14px;">Belum ada tahun akademik.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <dialog class="modal" id="add-tahun" @if ($errors->any()) data-show @endif>
        <form class="modal-form" method="POST" action="{{ route('kesantrian.tahun-akademik.store') }}">
            @csrf
            <div class="modal-head">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--brand);">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <h2>Tambah tahun akademik</h2>
                </div>
                <button type="button" class="btn-modal-close" data-close aria-label="Tutup">&times;</button>
            </div>
            <div style="display: grid; gap: 14px; margin-top: 14px;">
                <label>
                    Tahun
                    <input name="tahun" value="{{ old('tahun') }}" inputmode="numeric" maxlength="4" placeholder="Contoh: 2024" autocomplete="off" required>
                </label>
                <label>
                    Semester
                    <select name="semester" required>
                        <option value="GANJIL" @selected(old('semester', 'GANJIL') === 'GANJIL')>Ganjil</option>
                        <option value="GENAP" @selected(old('semester') === 'GENAP')>Genap</option>
                    </select>
                </label>
                <p class="note" style="margin: 0;">Tersimpan sebagai format: <code>2024/2025 - GANJIL</code>.</p>
            </div>
            <div class="modal-actions" style="margin-top: 20px; padding: 14px 0 0;">
                <button type="button" class="ghost" data-close>Batal</button>
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
    </dialog>
@endsection
