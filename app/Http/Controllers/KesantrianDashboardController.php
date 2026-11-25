<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Kesantrian\DataPelanggaranController;
use App\Http\Controllers\Kesantrian\DataRaportKesantrianController;
use App\Http\Controllers\Kesantrian\InputPelanggaranController;
use App\Http\Controllers\Kesantrian\InputRaportKesantrianController;
use App\Http\Controllers\Kesantrian\KategoriRaportController;
use App\Http\Controllers\Kesantrian\RaportTahfidzController;
use App\Http\Controllers\Kesantrian\ReferensiPelanggaranController;
use App\Http\Controllers\Kesantrian\TahunAkademikController;
use App\Kesantrian\KesantrianMenu;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class KesantrianDashboardController extends Controller
{
    public function index(): View
    {
        return view('kesantrian.home', [
            'account' => auth()->user(),
            'groups' => KesantrianMenu::groups(),
            'current' => null,
        ]);
    }

    public function show(string $menu): View|RedirectResponse
    {
        $item = KesantrianMenu::find($menu);
        abort_if($item === null, 404);

        if ($item['slug'] === 'tahun-akademik') {
            return app(TahunAkademikController::class)->index();
        }

        if ($item['slug'] === 'master-pelanggaran') {
            return app(ReferensiPelanggaranController::class)->index();
        }

        if ($item['slug'] === 'master-kategori-raport') {
            return app(KategoriRaportController::class)->index();
        }

        if ($item['slug'] === 'input-pelanggaran') {
            return app(InputPelanggaranController::class)->index(request());
        }

        if ($item['slug'] === 'data-pelanggaran') {
            return app(DataPelanggaranController::class)->index(request());
        }

        if ($item['slug'] === 'input-raport-kesantrian') {
            return app(InputRaportKesantrianController::class)->index(request());
        }

        if ($item['slug'] === 'data-raport-kesantrian') {
            return app(DataRaportKesantrianController::class)->index(request());
        }

        if ($item['slug'] === 'upload-raport') {
            return app(RaportTahfidzController::class)->upload();
        }

        if ($item['slug'] === 'data-raport') {
            return app(RaportTahfidzController::class)->data(request());
        }

        return view('kesantrian.page', [
            'account' => auth()->user(),
            'groups' => KesantrianMenu::groups(),
            'current' => $item,
        ]);
    }
}
