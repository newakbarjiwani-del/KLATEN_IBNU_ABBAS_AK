@extends('kesantrian.partials.shell')

@section('title', 'Master Kategori Raport — Kesantrian')

@section('desk')
    @include('kesantrian.partials.page-head', [
        'kicker' => $current['group'],
        'heading' => 'Master Kategori Raport',
        'summary' => 'Kategori dan butir raport kesantrian untuk unit '.$selectedUnit.'. Catatan tidak diatur di sini karena selalu tampil.',
    ])

    @if (session('status'))
        <div class="alert is-status"><span>{{ session('status') }}</span></div>
    @endif
    @if (session('error'))
        <div class="alert is-danger"><span>{{ session('error') }}</span></div>
    @endif

    @include('kesantrian.partials.unit-tabs', ['menu' => 'master-kategori-raport'])

    <section class="panel-block">
        <div class="panel-block-head">
            <h2>Tambah kategori</h2>
        </div>
        <form method="POST" action="{{ route('kesantrian.master-kategori-raport.kategori.store') }}" class="kategori-edit">
            @csrf
            <input type="hidden" name="unit" value="{{ $selectedUnit }}">
            <label>Nama kategori<input name="nama" value="{{ old('nama') }}" maxlength="80" required placeholder="Contoh: Ibadah"></label>
            <label>Urutan<input type="number" name="urut" min="1" max="999" value="{{ old('urut') }}" placeholder="Otomatis"></label>
            <button type="submit">Tambah kategori</button>
        </form>
    </section>

    @forelse ($kategori as $kelompok)
        <section class="panel-block kategori-card">
            <div class="panel-block-head">
                <h2>{{ $kelompok->nama }}</h2>
                <div class="panel-block-meta">
                    <span class="pill-item">
                        <span class="pill-label">Urutan</span>
                        <span class="pill-value">{{ $kelompok->urut }}</span>
                    </span>
                    <span class="pill-item">
                        <span class="pill-label">Butir</span>
                        <span class="pill-value is-highlight">{{ $kelompok->items->count() }}</span>
                    </span>
                </div>
            </div>

            <div class="kategori-toolbar">
                <form method="POST" action="{{ route('kesantrian.master-kategori-raport.kategori.update', $kelompok->id) }}" class="kategori-edit">
                    @csrf
                    @method('PUT')
                    <label>Nama kategori<input name="nama" value="{{ $kelompok->nama }}" maxlength="80" required></label>
                    <label>Urutan<input type="number" name="urut" min="1" max="999" value="{{ $kelompok->urut }}" required></label>
                    <button type="submit">Simpan kategori</button>
                </form>
                <form method="POST" action="{{ route('kesantrian.master-kategori-raport.kategori.destroy', $kelompok->id) }}" onsubmit="return confirm('Hapus kategori ini? Nilai lama tetap tersimpan.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-row-delete">Hapus kategori</button>
                </form>
            </div>

            <div class="butir-sheet">
                <div class="butir-head">
                    <span>Urut</span>
                    <span>Nama butir</span>
                    <span>Aksi</span>
                </div>
                @forelse ($kelompok->items as $item)
                    <div class="butir-row">
                        <form method="POST" action="{{ route('kesantrian.master-kategori-raport.item.update', $item->id) }}" class="butir-edit">
                            @csrf
                            @method('PUT')
                            <input type="number" name="urut" min="1" max="999" value="{{ $item->urut }}" required aria-label="Urutan {{ $item->nama }}">
                            <input name="nama" value="{{ $item->nama }}" maxlength="80" required aria-label="Nama butir {{ $item->nama }}">
                            <button type="submit">Simpan</button>
                        </form>
                        <form method="POST" action="{{ route('kesantrian.master-kategori-raport.item.destroy', $item->id) }}" onsubmit="return confirm('Hapus butir ini? Nilai lama tetap tersimpan.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-row-delete">Hapus</button>
                        </form>
                    </div>
                @empty
                    <p class="butir-empty">Belum ada butir pada kategori ini.</p>
                @endforelse
                <form method="POST" action="{{ route('kesantrian.master-kategori-raport.item.store') }}" class="butir-add">
                    @csrf
                    <input type="hidden" name="kategori_id" value="{{ $kelompok->id }}">
                    <input type="number" name="urut" min="1" max="999" placeholder="Auto" aria-label="Urutan butir baru">
                    <input name="nama" maxlength="80" required placeholder="Nama butir baru" aria-label="Nama butir baru">
                    <button type="submit">Tambah butir</button>
                </form>
            </div>
        </section>
    @empty
        <section class="panel-block">
            <p class="empty-state-box">Belum ada kategori untuk unit {{ $selectedUnit }}.</p>
        </section>
    @endforelse
@endsection
