<div style="display: grid; gap: 14px; margin-top: 14px;">
    @if ($lockedUnit ?? null)
        <input type="hidden" name="unit" value="{{ $lockedUnit }}">
    @else
        <label>
            Unit
            <select name="unit" required>
                <option value="">Pilih unit</option>
                @foreach ($units as $unit)
                    <option value="{{ $unit }}" @selected(old('unit', $selectedUnit ?? '') === $unit)>{{ $unit }}</option>
                @endforeach
            </select>
        </label>
    @endif
    <label>
        Nama pelanggaran
        <textarea name="nama" rows="3" maxlength="1000" placeholder="Ketik jenis atau nama pelanggaran..." required>{{ old('nama') }}</textarea>
    </label>
    <label>
        Skor
        <input name="skor" type="number" min="0" max="32767" value="{{ old('skor') }}" placeholder="Contoh: 10" inputmode="numeric" required>
    </label>
    <label>
        Keterangan
        <input name="keterangan" value="{{ old('keterangan') }}" maxlength="20" placeholder="Contoh: SPT">
    </label>
</div>
<div class="modal-actions" style="margin-top: 20px; padding: 14px 0 0;">
    <button type="button" class="ghost" data-close>Batal</button>
    <button type="submit" class="btn-primary">Simpan</button>
</div>
