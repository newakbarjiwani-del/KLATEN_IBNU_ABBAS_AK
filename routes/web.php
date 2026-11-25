<?php

use App\Http\Controllers\Akademik\RaportAkademikController;
use App\Http\Controllers\AkademikDashboardController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Kesantrian\DataPelanggaranController;
use App\Http\Controllers\Kesantrian\DataRaportKesantrianController;
use App\Http\Controllers\Kesantrian\InputPelanggaranController;
use App\Http\Controllers\Kesantrian\InputRaportKesantrianController;
use App\Http\Controllers\Kesantrian\KategoriRaportController;
use App\Http\Controllers\Kesantrian\RaportTahfidzController;
use App\Http\Controllers\Kesantrian\ReferensiPelanggaranController;
use App\Http\Controllers\Kesantrian\TahunAkademikController;
use App\Http\Controllers\KesantrianDashboardController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\Tahfidz\HafalanController;
use App\Http\Controllers\TahfidzDashboardController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $account = auth()->user();

    if ($account === null) {
        return redirect()->route('login');
    }

    if ($account->homeRoute() === null) {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    }

    return redirect()->route($account->homeRoute());
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/beranda', [PortalController::class, 'index'])->name('portal.home');

    Route::middleware('system:admtah')->prefix('tahfidz')->group(function () {
        Route::get('/', [TahfidzDashboardController::class, 'index'])->name('tahfidz.home');
        Route::get('/data-setoran/ekspor', [HafalanController::class, 'eksporSetoran'])->name('tahfidz.data-setoran.export');
        Route::get('/setoran-lama/ekspor', [HafalanController::class, 'eksporLama'])->name('tahfidz.setoran-lama.export');
        Route::get('/laporan-capaian/cetak', [HafalanController::class, 'cetakLaporan'])->name('tahfidz.laporan-capaian.cetak');
        Route::get('/{kind}/template', [HafalanController::class, 'template'])->whereIn('kind', ['upload-tahfidz'])->name('tahfidz.hafalan.template');
        Route::post('/{kind}/buka', [HafalanController::class, 'buka'])->whereIn('kind', ['upload-tahfidz'])->name('tahfidz.hafalan.open');
        Route::post('/{kind}/simpan', [HafalanController::class, 'simpan'])->whereIn('kind', ['upload-tahfidz'])->name('tahfidz.hafalan.save');
        Route::get('/{menu}', [TahfidzDashboardController::class, 'show'])->name('tahfidz.menu');
    });

    Route::middleware('system:admkes')->prefix('kesantrian')->group(function () {
        Route::get('/', [KesantrianDashboardController::class, 'index'])->name('kesantrian.home');
        Route::post('/tahun-akademik', [TahunAkademikController::class, 'store'])->name('kesantrian.tahun-akademik.store');
        Route::post('/master-pelanggaran', [ReferensiPelanggaranController::class, 'store'])->name('kesantrian.master-pelanggaran.store');
        Route::put('/master-pelanggaran/{urut}', [ReferensiPelanggaranController::class, 'update'])->whereNumber('urut')->name('kesantrian.master-pelanggaran.update');
        Route::delete('/master-pelanggaran/{urut}', [ReferensiPelanggaranController::class, 'destroy'])->whereNumber('urut')->name('kesantrian.master-pelanggaran.destroy');
        Route::get('/master-pelanggaran/template', [ReferensiPelanggaranController::class, 'template'])->name('kesantrian.master-pelanggaran.template');
        Route::post('/master-pelanggaran/unggah', [ReferensiPelanggaranController::class, 'import'])->name('kesantrian.master-pelanggaran.import');
        Route::post('/master-kategori-raport/kategori', [KategoriRaportController::class, 'storeKategori'])->name('kesantrian.master-kategori-raport.kategori.store');
        Route::put('/master-kategori-raport/kategori/{id}', [KategoriRaportController::class, 'updateKategori'])->whereNumber('id')->name('kesantrian.master-kategori-raport.kategori.update');
        Route::delete('/master-kategori-raport/kategori/{id}', [KategoriRaportController::class, 'destroyKategori'])->whereNumber('id')->name('kesantrian.master-kategori-raport.kategori.destroy');
        Route::post('/master-kategori-raport/butir', [KategoriRaportController::class, 'storeItem'])->name('kesantrian.master-kategori-raport.item.store');
        Route::put('/master-kategori-raport/butir/{id}', [KategoriRaportController::class, 'updateItem'])->whereNumber('id')->name('kesantrian.master-kategori-raport.item.update');
        Route::delete('/master-kategori-raport/butir/{id}', [KategoriRaportController::class, 'destroyItem'])->whereNumber('id')->name('kesantrian.master-kategori-raport.item.destroy');
        Route::get('/input-pelanggaran/santri', [InputPelanggaranController::class, 'santri'])->name('kesantrian.input-pelanggaran.santri');
        Route::post('/input-pelanggaran', [InputPelanggaranController::class, 'store'])->name('kesantrian.input-pelanggaran.store');
        Route::get('/data-pelanggaran/detail', [DataPelanggaranController::class, 'detail'])->name('kesantrian.data-pelanggaran.detail');

        Route::get('/input-raport-kesantrian/template', [InputRaportKesantrianController::class, 'template'])->name('kesantrian.input-raport-kesantrian.template');
        Route::post('/input-raport-kesantrian/preview', [InputRaportKesantrianController::class, 'preview'])->name('kesantrian.input-raport-kesantrian.preview');
        Route::post('/input-raport-kesantrian', [InputRaportKesantrianController::class, 'store'])->name('kesantrian.input-raport-kesantrian.store');

        Route::get('/santri', [RaportTahfidzController::class, 'suggest'])->name('kesantrian.santri.suggest');
        Route::get('/upload-raport/santri', [RaportTahfidzController::class, 'santri'])->name('kesantrian.upload-raport.santri');
        Route::post('/upload-raport', [RaportTahfidzController::class, 'store'])->name('kesantrian.upload-raport.store');
        Route::get('/data-raport/ekspor', [RaportTahfidzController::class, 'export'])->name('kesantrian.data-raport.export');
        Route::get('/data-raport/{urut}/berkas', [RaportTahfidzController::class, 'file'])->whereNumber('urut')->name('kesantrian.data-raport.file');

        Route::get('/data-raport-kesantrian/ekspor', [DataRaportKesantrianController::class, 'export'])->name('kesantrian.data-raport-kesantrian.export');
        Route::get('/data-raport-kesantrian/{urut}/unduh', [DataRaportKesantrianController::class, 'cetak'])->whereNumber('urut')->name('kesantrian.data-raport-kesantrian.unduh');
        Route::delete('/data-raport-kesantrian/bulk', [DataRaportKesantrianController::class, 'bulkDestroy'])->name('kesantrian.data-raport-kesantrian.bulk');
        Route::delete('/data-raport-kesantrian/{urut}', [DataRaportKesantrianController::class, 'destroy'])->name('kesantrian.data-raport-kesantrian.destroy');

        Route::get('/{menu}', [KesantrianDashboardController::class, 'show'])->name('kesantrian.menu');
    });

    Route::middleware('system:admaka')->prefix('akademik')->group(function () {
        Route::get('/', [AkademikDashboardController::class, 'index'])->name('akademik.home');
        Route::get('/santri', [RaportAkademikController::class, 'suggest'])->name('akademik.santri.suggest');
        Route::post('/upload-raport/pratinjau', [RaportAkademikController::class, 'pratinjau'])->name('akademik.upload-raport.preview');
        Route::post('/upload-raport', [RaportAkademikController::class, 'store'])->name('akademik.upload-raport.store');
        Route::get('/data-raport/ekspor', [RaportAkademikController::class, 'export'])->name('akademik.data-raport.export');
        Route::get('/data-raport/{urut}/berkas', [RaportAkademikController::class, 'file'])->whereNumber('urut')->name('akademik.data-raport.file');
        Route::get('/{menu}', [AkademikDashboardController::class, 'show'])->name('akademik.menu');
    });
});
