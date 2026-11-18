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
            <h2>Upload berkas</h2>
        </div>
        <form class="sheet" id="form-raport" method="POST" action="{{ route('kesantrian.upload-raport.store') }}" enctype="multipart/form-data">
            @csrf
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
                NIS / Nama
                <span class="nis-lookup">
                    <input name="nocust" value="{{ old('nocust') }}" maxlength="70" placeholder="Ketik NIS atau nama santri..." autocomplete="off" required data-nis-suggest="{{ route('kesantrian.santri.suggest') }}" data-nis-target="nama-santri">
                    <div class="nis-menu" hidden></div>
                </span>
            </label>
            <label>
                Nama
                <input id="nama-santri" value="" placeholder="Otomatis terisi dari NIS" readonly>
            </label>
            <label>
                Tanggal upload
                <input type="date" name="tgl_upload" value="{{ old('tgl_upload', now()->toDateString()) }}" required>
            </label>
            <label>
                Berkas PDF
                <input type="file" name="berkas" accept="application/pdf,.pdf" required style="padding-top: 8px;">
            </label>
            <label>
                Ujian
                <select name="ujian" required>
                    <option value="MID" @selected(old('ujian', 'MID') === 'MID')>Mid</option>
                    <option value="AKHIR" @selected(old('ujian') === 'AKHIR')>Akhir</option>
                </select>
            </label>
            <div class="sheet-actions">
                <button type="submit" class="btn-primary">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;">
                        <polyline points="16 16 12 12 8 16"></polyline>
                        <line x1="12" y1="12" x2="12" y2="21"></line>
                        <path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"></path>
                    </svg>
                    Mulai upload
                </button>
            </div>
        </form>
    </section>

    @include('partials.upload-sukses')
@endsection
