@extends('akademik.partials.shell')

@section('title', $current['label'].' — Raport Akademik')

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
        <form class="sheet" method="POST" action="{{ route('akademik.upload-raport.store') }}" enctype="multipart/form-data">
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
                    <input name="nocust" value="{{ old('nocust') }}" maxlength="70" placeholder="Ketik NIS atau nama santri..." autocomplete="off" required data-nis-suggest="{{ route('akademik.santri.suggest') }}" data-nis-target="nama-santri" data-nis-kelas="kelas-santri">
                    <div class="nis-menu" hidden></div>
                </span>
            </label>
            <label>
                Kelas
                <select name="kelas" id="kelas-santri" required>
                    <option value="">Pilih kelas</option>
                    @foreach ($classes as $pilihanKelas)
                        <option value="{{ $pilihanKelas['kunci'] }}" @selected(old('kelas') === $pilihanKelas['kunci'])>{{ $pilihanKelas['label'] }}</option>
                    @endforeach
                </select>
            </label>
            <label class="is-wide">
                Nama
                <input id="nama-santri" value="" placeholder="Otomatis terisi dari NIS" readonly>
            </label>
            <label>
                Ujian
                <select name="ujian" required>
                    <option value="MID" @selected(old('ujian', 'MID') === 'MID')>Mid</option>
                    <option value="AKHIR" @selected(old('ujian') === 'AKHIR')>Akhir</option>
                </select>
            </label>
            <label>
                Tanggal upload
                <input type="date" name="tgl_upload" value="{{ old('tgl_upload', now()->toDateString()) }}" required>
            </label>
            <label>
                Berkas PDF
                <input type="file" name="berkas" accept="application/pdf,.pdf" required style="padding-top: 8px;">
                <small class="field-note">Hanya berkas PDF. Ukuran paling besar 10 MB.</small>
            </label>
            <div class="sheet-actions">
                <button type="submit" class="btn-primary">Mulai upload</button>
            </div>
        </form>
    </section>

    @include('partials.upload-sukses')
@endsection
