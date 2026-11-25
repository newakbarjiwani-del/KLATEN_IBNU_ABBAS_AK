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
            <label class="is-wide">
                Berkas PDF
                <input type="file" name="berkas[]" accept="application/pdf,.pdf" multiple required data-berkas-raport data-pratinjau="{{ route('akademik.upload-raport.preview') }}" style="padding-top: 8px;">
                <small class="field-note">Nama berkas NIS_jenis.pdf. Contoh Mid: 1301154344_type1.pdf dan 1301154344_type2.pdf. Satu siswa boleh punya lebih dari satu berkas. Paling banyak 20 berkas, masing-masing 10 MB.</small>
            </label>
            <div class="is-wide raport-pratinjau" id="pratinjau-raport" hidden>
                <h3>Pratinjau siswa</h3>
                <div id="pratinjau-isi"></div>
            </div>
            <div class="sheet-actions">
                <button type="submit" class="btn-primary">Mulai upload</button>
            </div>
        </form>
    </section>

    @include('partials.upload-sukses')

    <script>
        (function () {
            var input = document.querySelector('[data-berkas-raport]');
            var panel = document.getElementById('pratinjau-raport');
            var isi = document.getElementById('pratinjau-isi');
            var form = input ? input.closest('form') : null;

            if (!input || !panel || !isi || !form) {
                return;
            }

            var tampilkan = function (data) {
                isi.replaceChildren();
                var siswa = data.siswa || [];
                var gagal = data.gagal || [];

                if (siswa.length === 0 && gagal.length === 0) {
                    panel.hidden = true;
                    return;
                }

                panel.hidden = false;
                siswa.forEach(function (orang) {
                    var kartu = document.createElement('article');
                    kartu.className = 'pratinjau-siswa' + (orang.ditemukan ? '' : ' is-missing');

                    var kepala = document.createElement('p');
                    var nama = document.createElement('strong');
                    nama.textContent = orang.ditemukan ? orang.nama : 'Siswa tidak ditemukan';
                    var nis = document.createElement('span');
                    nis.textContent = '· ' + orang.nis;
                    kepala.append(nama, nis);
                    kartu.appendChild(kepala);

                    var berkas = orang.berkas || [];
                    if (berkas.length > 1) {
                        var daftar = document.createElement('ul');
                        berkas.forEach(function (namaBerkas) {
                            var butir = document.createElement('li');
                            butir.textContent = namaBerkas;
                            daftar.appendChild(butir);
                        });
                        kartu.appendChild(daftar);
                    } else if (berkas.length === 1) {
                        var satu = document.createElement('p');
                        satu.className = 'pratinjau-satu';
                        satu.textContent = berkas[0];
                        kartu.appendChild(satu);
                    }

                    isi.appendChild(kartu);
                });

                gagal.forEach(function (item) {
                    var baris = document.createElement('p');
                    baris.className = 'pratinjau-gagal';
                    baris.textContent = item.nama + ' — ' + item.alasan;
                    isi.appendChild(baris);
                });
            };

            input.addEventListener('change', function () {
                var files = Array.prototype.slice.call(input.files || []);
                isi.replaceChildren();

                if (files.length === 0) {
                    panel.hidden = true;
                    return;
                }

                panel.hidden = false;
                var tunggu = document.createElement('p');
                tunggu.className = 'pratinjau-tunggu';
                tunggu.textContent = 'Membaca nama siswa...';
                isi.appendChild(tunggu);

                var body = new FormData();
                var token = form.querySelector('input[name="_token"]');
                body.append('_token', token ? token.value : '');
                files.forEach(function (file) {
                    body.append('nama[]', file.name);
                });

                fetch(input.getAttribute('data-pratinjau'), {
                    method: 'POST',
                    body: body,
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }).then(function (response) {
                    if (!response.ok) {
                        throw new Error('gagal');
                    }
                    return response.json();
                }).then(tampilkan).catch(function () {
                    isi.replaceChildren();
                    var gagal = document.createElement('p');
                    gagal.className = 'pratinjau-gagal';
                    gagal.textContent = 'Nama siswa tidak dapat dimuat. Periksa koneksi, lalu pilih berkas lagi.';
                    isi.appendChild(gagal);
                });
            });
        })();
    </script>
@endsection
