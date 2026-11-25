<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Akhlak {{ trim((string) $row->NMCUST) }}</title>
    <style>
        @page { size: A4; margin: 10mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #111; font-family: "Times New Roman", Times, serif; background: #d9d9d9; }
        .alat { position: sticky; top: 0; z-index: 5; display: flex; justify-content: flex-end; gap: 8px; padding: 10px 16px; background: #102433; }
        .alat button { height: 34px; padding: 0 14px; border: 0; border-radius: 2px; background: #0875ab; color: #fff; font-family: "Segoe UI", sans-serif; font-size: 13px; font-weight: 600; cursor: pointer; }
        .lembar { position: relative; width: 210mm; min-height: 297mm; margin: 12px auto; padding: 10mm 16mm 20mm; background: #fff; overflow: hidden; }
        .air { position: absolute; inset: -34% -12%; z-index: 0; display: flex; flex-direction: column; justify-content: space-evenly; transform: rotate(-32deg); pointer-events: none; }
        .air span { color: rgba(126, 186, 220, 0.42); font-family: "Segoe UI", Tahoma, sans-serif; font-size: 14px; font-weight: 600; letter-spacing: 0.2em; white-space: nowrap; }
        .cap-tengah { position: absolute; z-index: 0; left: 50%; top: 46%; width: 340px; transform: translate(-50%, -50%); opacity: 0.08; pointer-events: none; }
        .isi { position: relative; z-index: 1; }
        .kop { display: block; width: 100%; height: auto; }
        .kepala { display: grid; grid-template-columns: 92px 1fr; gap: 8px; align-items: center; }
        .kepala img { width: 86px; height: 86px; object-fit: contain; }
        .naskah { text-align: center; }
        .naskah strong { display: block; font-size: 15.5px; line-height: 1.2; letter-spacing: 0.01em; }
        .naskah p { margin: 3px 0 0; font-size: 11.5px; line-height: 1.35; }
        .naskah a { color: #1a66c2; }
        .garis-kop { height: 0; margin: 7px 0 10px; border: 0; border-top: 2.5px solid #222; }
        .judul { margin: 8px 0 20px; text-align: center; font-size: 17px; font-weight: 700; letter-spacing: 0.02em; }
        .identitas { width: auto; margin: 0 0 6px 22px; border-collapse: collapse; font-size: 15px; }
        .identitas th { width: 156px; padding: 3px 0; font-weight: 700; text-align: left; }
        .identitas td { padding: 3px 10px 3px 0; font-weight: 400; }
        .identitas td.titik { width: 16px; padding-right: 10px; }
        .bagian { margin: 18px 0 5px; font-size: 15px; font-weight: 700; letter-spacing: 0.04em; }
        table.nilai { width: 100%; border-collapse: collapse; font-size: 14px; background: transparent; }
        table.nilai th, table.nilai td { border: 1px solid #111; padding: 5px 8px; background: transparent; }
        table.nilai th { font-weight: 700; text-align: center; }
        table.nilai td.no { width: 46px; text-align: center; }
        table.nilai td.ket { width: 36%; text-align: center; }
        .catatan-judul { margin: 28px 0 12px; text-align: center; font-size: 15px; font-weight: 700; }
        .catatan { min-height: 132px; padding: 16px 22px; border: 1px solid #111; font-size: 14.5px; line-height: 1.55; text-align: center; }
        .ttd { display: grid; grid-template-columns: 1fr 1fr; margin-top: 28px; font-size: 15px; text-align: center; }
        .ttd .tanggal { grid-column: 2; margin: 0 0 18px; font-weight: 700; }
        .ttd .jabatan { font-weight: 700; }
        .ttd .ruang { height: 52px; }
        .ttd-wali { display: block; height: 46px; margin: 4px auto 2px; }
        .ttd .garis { width: 210px; margin: 0 auto; border-top: 1px solid #222; }
        .ttd .nama-ttd { margin-top: 4px; font-weight: 700; }
        .stempel { display: block; width: 252px; height: auto; margin: 6px auto 0; }
        .mengetahui { margin-top: 16px; text-align: center; font-size: 15px; }
        .mengetahui .jabatan, .mengetahui .nama-ttd { font-weight: 700; }
        .kaki { position: absolute; z-index: 1; right: 16mm; bottom: 9mm; left: 16mm; }
        .kaki-garis { display: flex; align-items: flex-start; }
        .kaki-bar { flex: 1; height: 8px; background: linear-gradient(90deg, #b9ddf4, #8ec6ea); }
        .kaki-nomor { width: 28px; height: 24px; margin-left: 0; display: flex; align-items: center; justify-content: center; background: #79b7e2; color: #fff; font-family: "Segoe UI", sans-serif; font-size: 13px; font-weight: 700; }
        .kaki-teks { margin-top: 2px; color: #3d92c6; font-family: "Times New Roman", Times, serif; font-size: 12px; font-style: italic; }
        @media print {
            body { background: #fff; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .alat { display: none; }
            .lembar { width: auto; min-height: 270mm; margin: 0; page-break-after: always; }
        }
    </style>
</head>
<body>
    <div class="alat">
        <button type="button" onclick="window.print()">Cetak / simpan PDF</button>
    </div>
    @php
        $nama = trim((string) $row->NMCUST);
        $nis = trim((string) $row->NOCUST);
        $kelas = trim((string) ($row->kelas ?? ''));
        $kamar = trim((string) ($row->kelompok ?? ''));
        $semester = $periode['semester'];
        $tahun = $periode['tahun'];
        $kaki = collect([$nama, $nis, $kelas, $kamar, $semester, $tahun])->filter(fn (string $nilai): bool => $nilai !== '')->implode(' / ');
        $huruf = 0;
    @endphp

    <section class="lembar">
        @include('kesantrian.partials.raport-cetak-latar', ['logo' => $logo])
        <div class="isi">
        @if ($kop !== '')
            <img class="kop" src="{{ $kop }}" alt="Kop Kesantrian Putri Ibnu Abbas Klaten">
        @else
            <header class="kepala">
                @if ($logo !== '')
                    <img src="{{ $logo }}" alt="Kesantrian Putri">
                @else
                    <span></span>
                @endif
                <div class="naskah">
                    <strong>PONDOK PESANTREN TAHFIDZUL QUR'AN</strong>
                    <strong>SEKOLAH MENENGAH ATAS ISLAM TERPADU</strong>
                    <strong>IBNU ABBAS KLATEN</strong>
                    <p>Alamat : Jl. Klaten-Solo Km 04, Belangwetan, Klaten Utara, Klaten, Jawa Tengah<br>Telp 0821-3618-3960 Email: <a href="mailto:officialkesantrianputriibbas@gmail.com">officialkesantrianputriibbas@gmail.com</a></p>
                </div>
            </header>
            <hr class="garis-kop">
        @endif
        <h2 class="judul">LAPORAN AKHLAK SANTRIWATI</h2>
        <table class="identitas">
            <tr><th>Nama</th><td class="titik">:</td><td>{{ $nama }}</td></tr>
            <tr><th>No. Induk</th><td class="titik">:</td><td>{{ $nis }}</td></tr>
            <tr><th>Kelas</th><td class="titik">:</td><td>{{ $kelas }}</td></tr>
            <tr><th>TP/Semester</th><td class="titik">:</td><td>{{ $tahun }}{{ $semester !== '' ? '/'.$semester : '' }}</td></tr>
            <tr><th>Kamar</th><td class="titik">:</td><td>{{ $kamar }}</td></tr>
        </table>
        @foreach ($halamanPertama as $kelompok)
            @include('kesantrian.partials.raport-cetak-bagian', ['kelompok' => $kelompok, 'huruf' => chr(65 + $huruf), 'nilai' => $row->nilai])
            @php $huruf++; @endphp
        @endforeach
        </div>
        @include('kesantrian.partials.raport-cetak-kaki', ['kaki' => $kaki, 'nomor' => 1])
    </section>

    <section class="lembar">
        @include('kesantrian.partials.raport-cetak-latar', ['logo' => $logo])
        <div class="isi">
        @foreach ($halamanKedua as $kelompok)
            @include('kesantrian.partials.raport-cetak-bagian', ['kelompok' => $kelompok, 'huruf' => chr(65 + $huruf), 'nilai' => $row->nilai])
            @php $huruf++; @endphp
        @endforeach
        <h3 class="catatan-judul">Catatan Wali Kamar</h3>
        <div class="catatan">@if (filled($row->Catatan))“ {{ $row->Catatan }} ”@endif</div>
        <div class="ttd">
            <div></div>
            <div class="tanggal">Klaten, {{ $tanggalTtd }}</div>
            <div>
                <div class="jabatan">Orang Tua / Wali Santri</div>
                <div class="ruang"></div>
                <div class="garis"></div>
            </div>
            <div>
                <div class="jabatan">Wali Kamar</div>
                @if ($tandaTangan !== '')
                    <img class="ttd-wali" src="{{ $tandaTangan }}" alt="">
                @else
                    <div class="ruang"></div>
                @endif
                <div class="nama-ttd">Syahidati Nur Shofi, S.Ag</div>
            </div>
        </div>
        @if ($stempel !== '')
            <img class="stempel" src="{{ $stempel }}" alt="Mengetahui, Kepala Kesantrian Putri">
        @else
            <div class="mengetahui">
                <div>Mengetahui,</div>
                <div class="jabatan">Kepala Kesantrian Putri</div>
                <div class="nama-ttd">Dr. Hj. Elly Damaiwati, S.S., M.Pd</div>
            </div>
        @endif
        </div>
        @include('kesantrian.partials.raport-cetak-kaki', ['kaki' => $kaki, 'nomor' => 2])
    </section>
</body>
</html>
