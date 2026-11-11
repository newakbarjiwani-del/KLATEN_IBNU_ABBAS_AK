# PPTQ Ibnu Abbas Klaten

Aplikasi Laravel untuk tiga sistem dalam satu login: Tahfidz, Kesantrian, dan Raport Akademik. Database MySQL sudah dipakai operasional. Perubahan skema tidak lewat migrasi Laravel.

## Batasan database

- Koneksi ada di `.env`. Jangan menyalin kata sandi ke berkas, commit, atau balasan.
- Jangan menjalankan `php artisan migrate`, `migrate:fresh`, `db:wipe`, atau SQL `DROP` / `DELETE` / `TRUNCATE` pada database live.
- `SESSION_DRIVER=file`, `CACHE_STORE=file`, dan `QUEUE_CONNECTION=sync` supaya Laravel tidak membuat tabel session, cache, atau jobs.
- Tes memakai SQLite `:memory:` lewat `phpunit.xml`. Tes tidak boleh menyentuh MySQL live.
- Host MySQL kadang tidak menjawab. `App\Database\Connectors\TimeoutMySqlConnector` menolak koneksi yang diam lebih dari 3 detik, sebelum PDO menggantung.

## Login

Akun ada di tabel `cyber_key`. Modelnya `App\Models\CyberKey`. Primary key adalah `users` (string), tanpa timestamps. Login menerima sandi di kolom `password` atau, bila tidak cocok, sandi di kolom `kunci`. Kolom `kunci` tidak pernah diubah.

Form login, dari atas ke bawah: **Aplikasi**, **Username**, **Password**. Pilihan aplikasi adalah kode `kel`, bukan halaman terpisah setelah login.

| Aplikasi | `kel` | Route |
| --- | --- | --- |
| Tahfidz | `admtah` | `tahfidz.home` |
| Kesantrian | `admkes` | `kesantrian.home` |
| Raport Akademik | `admaka` | `akademik.home` |

Aplikasi yang dipilih harus sama dengan `kel`: Raport Akademik `admaka`, Tahfidz `admtah`, Kesantrian `admkes`. Jika `password` masih kosong dan masuk lewat `kunci`, bcrypt disimpan ke `password` saja.

Akun hanya masuk ke aplikasi yang `kel`-nya sama dengan pilihan. Middleware `system` mengembalikan pengguna yang salah aplikasi ke beranda sistemnya.

Dashboard Tahfidz memakai menu Data Setoran dan Tahfidz, Murajaah, Setoran, dan Setoran (OLD). Data Setoran dan Tahfidz menggabungkan `akademik_setoran` dan `akademik_murajaah`, disaring menurut tanggal, mode, dan periode. Murajaah dan Setoran membuka berkas CSV atau Excel, dengan template yang bisa diunduh, lalu menyimpan baris yang NIS-nya dikenali. Daftar setoran, setoran lama, dan data raport dapat diekspor ke Excel sesuai saringan yang sedang dipakai. Setoran (OLD) membaca `oldakademik_setoran`. Nama santri dari `scctcust`. Daftar periode disimpan sementara 10 menit.

Password diterima sebagai bcrypt, SHA1 hex 40 karakter, atau teks biasa yang panjangnya sama. Jangan memanggil `Hash::check()` pada hash non-bcrypt. Setelah `kel` cocok, password non-bcrypt ditulis ulang menjadi bcrypt pada kolom `password` saja. Baris tanpa password tidak bisa login. Perintah `php artisan cyber-key:password {users}` hanya mengubah kolom itu; jangan dijalankan pada data live kecuali diminta.

Kesalahan login, validasi, dan status sukses ditampilkan dengan Toastify, bukan kotak peringatan di dalam form.

## Kesantrian

Menu didefinisikan di `App\Kesantrian\KesantrianMenu`. Setiap item punya `slug`, `label`, dan `summary`. Route `GET /kesantrian/{menu}` mencari slug itu. Slug yang tidak ada menghasilkan 404. Halaman yang belum dibuat memakai `resources/views/kesantrian/page.blade.php` dan teks "belum diisi."

Route `POST` dan lookup harus didaftarkan sebelum `GET /kesantrian/{menu}`.

| Menu | Status | Tabel |
| --- | --- | --- |
| Master Tahun Akademik | daftar + tambah di dialog | `mst_thn_aka` |
| Master Pelanggaran | daftar + tambah di dialog | `akademik_ref_langgar` |
| Input Pelanggaran | form di halaman + riwayat | `akademik_pelanggaran` |
| Data Pelanggaran | daftar + filter + rincian | `akademik_pelanggaran`, `scctcust` |
| Input Raport Kesantrian | import xlsx/csv + preview + simpan | `akademik_raport_kesantrian`, `scctcust` |
| Data Raport Kesantrian | daftar + filter periode/tgl/nama + hapus | `akademik_raport_kesantrian`, `scctcust` |
| Upload Raport | unggah PDF, ujian Mid atau Akhir | `akademik_rapot_tahfidzh` |
| Data Raport | daftar dan unduh berkas yang tersimpan | `akademik_rapot_tahfidzh` |

Tahfidz dan Raport Akademik masih halaman awal `SystemHomeController`.

### `mst_thn_aka`

Model `App\Models\TahunAkademik`. Primary key `urut` autoincrement. Isi disimpan di `thn_aka`, varchar 18, misalnya `2024/2025 - GANJIL`. Form meminta tahun 4 digit dan semester Ganjil atau Genap, lalu menyusun string itu. Daftar diurutkan `urut` naik.

### `akademik_ref_langgar`

Model `App\Models\ReferensiPelanggaran`. Primary key `urut` autoincrement.

Nama kolom aslinya `jenis_pelangaran`, tanpa huruf g kedua. Jangan "diperbaiki" menjadi `jenis_pelanggaran`. Kolom lain: `skor` smallint, `keterangan` varchar 20. Daftar diurutkan `urut` naik. Nama yang sudah ada ditolak.

### `akademik_pelanggaran`

Model `App\Models\CatatanPelanggaran`. Primary key `urut` autoincrement. Santri dibaca dari `scctcust` lewat `NOCUST`; nama tampil dari `NMCUST` dan tidak disimpan di catatan.

| Jenis | Disimpan |
| --- | --- |
| Pelanggaran | `id_pelanggaran` = `urut` master, `skor_kredit` = skor master, `id_remisi` dan `skor_debet` kosong |
| Remisi | `id_remisi` = `urut` master, `skor_debet` = skor yang diketik, `id_pelanggaran` dan `skor_kredit` kosong |

Skor pelanggaran selalu diambil dari master, meskipun browser mengirim angka lain. Skor remisi diketik pengguna. `NOCUST` paling panjang 10 karakter. `thn_aka` harus sudah ada di `mst_thn_aka`. Riwayat terbaru ditampilkan di atas; nomor urut master tetap dari kecil ke besar.

### `akademik_raport_kesantrian`

Model `App\Models\RaportKesantrian`. Primary key `urut` autoincrement.
Kolom meliputi `NOCUST`, `tgl`, `periode`, `Catatan`, `IbadahA`..`IbadahE`, `AdabA`..`AdabE`, `KepribadianA`..`KepribadianE`, `PengembanganA`..`PengembanganC`.
Label di Input dan Data Raport mengikuti layar lama: Ibadah (Sholat Wajib, Sholat Dhuha, Sholat Lail, Puasa Sunnah, Sedekah), Adab (Menghormati, Berkata Sopan, Jujur, Tolong Menolong, Mengajak Kebaikan), Kepribadian (Kebersihan Diri, Kebersihan Lingkungan, Hubungan Sosial, Disiplin, Kesehatan), Pengembangan Diri (Pengembangan Diri, Aktif Organisasi, Literasi Buku), Catatan (Wali Hujroh). Nama kolom database tidak diubah.
Dapat diimpor lewat berkas Excel (`.xlsx`) atau CSV (`.csv`) via `RaportKesantrianSpreadsheetService`. Nama santri dicocokkan otomatis dari `scctcust`. Data dapat difilter berdasarkan tanggal, periode, maupun NIS/nama serta dapat dihapus satuan maupun massal.

### `akademik_rapot`

Model `App\Models\RaportAkademik`. Tabel live memakai ejaan `rapot`. Primary key `urut`. Kolom: `NOCUST`, `THN_AKA` (paling panjang 20), `FILE_RAPOT`, `UJIAN` (`MID` atau `AKHIR`). Dipakai menu Upload Raport dan Data Raport pada sistem Raport Akademik. Berkas PDF dikirim ke `http://101.50.2.56/Klaten_IbnuAbbas/uploadrapot/uploads/`. Nama santri dari `scctcust`. Jenjang dari `CODE02`, kelas dari `DESC02`, kelompok dari `DESC03`. Filter Kelas di Data Raport memilih ketiganya sekaligus.

### `akademik_rapot_tahfidzh`

Model `App\Models\RaportTahfidz`. Nama tabel live memakai `rapot` dan akhiran `tahfidzh`. Primary key `urut`. Kolom: `NOCUST`, `THN_AKA` (paling panjang 20), `FILE_RAPOT`, `UJIAN` (`MID` atau `AKHIR`). Berkas PDF dikirim ke `http://101.50.2.56/Klaten_IbnuAbbas/uploadrapottahfidzh/uploads/`. Nama santri dari `scctcust`. Kelas dari `DESC02`, kelompok dari `DESC03`, jenjang dari `CODE02`. Tahun akademik raport ada di `THN_AKA`. Daftar Data Raport dikelompokkan menurut santri. Kotak NIS menampilkan pilihan yang cocok saat diketik.

## Tampilan

Stylesheet: `public/css/portal.css`, dipanggil dari `resources/views/layouts/portal.blade.php` dengan query `?v=` agar cache browser pecah. Naikkan nomor itu bila CSS berubah.

Halaman masuk boleh diindeks. Halaman setelah login memakai `noindex, nofollow`, dan `public/robots.txt` menutup `/kesantrian`, `/tahfidz`, serta `/akademik`, karena isinya data santri. Toast pemberitahuan hanya dimuat saat ada pesan. Daftar kelas dan kelompok di filter disimpan sementara 10 menit. Di layar sempit, menu kesantrian menjadi laci yang dibuka dari bilah atas.

- Sudut 3px. Jangan membuat sudut besar.
- Biru `#0B7EB8` untuk tombol dan tautan. Oranye `#E89B0C` untuk nomor, garis aktif, dan aksen kecil.
- Sidebar dan panel login memakai navy, terpisah dari kanvas isi. Logo `public/logo.png` transparan, tanpa pelat putih.
- Mode terang/gelap lewat `html[data-theme]` dan `localStorage.theme`. Tombolnya ikon matahari/bulan, bukan teks.
- Tambah data master memakai `<dialog>`. Form input yang menjadi isi halaman tetap di halaman.
- Indikator proses hanya bilah `#page-loader` di tepi atas. Bilah itu muncul saat pindah menu, mengirim form, dan permintaan di dalam halaman lewat `window.portalProgress`.
- Sidebar Kesantrian sticky. Setiap halaman punya judul dan `summary`.
- Bahasa antarmuka Indonesia.

## Menambah menu Kesantrian

1. Tambah item di `KesantrianMenu` dengan `summary`.
2. Buat controller di `App\Http\Controllers\Kesantrian`, form request bila ada simpanan, dan model bila tabelnya belum dibungkus.
3. Arahkan slug itu dari `KesantrianDashboardController::show()`.
4. Daftarkan route tulis sebelum wildcard `{menu}`.
5. Ikuti pola dialog untuk master, atau form `.sheet` untuk halaman input.
6. Tambah tes fitur di SQLite. Jangan mengandalkan MySQL live.
7. Perbarui tabel menu di berkas ini.

## Tes

```sh
php artisan test --compact
```

Tes login, dasbor, tahun akademik, master pelanggaran, dan input pelanggaran membuat tabel sendiri di `setUp()`.
