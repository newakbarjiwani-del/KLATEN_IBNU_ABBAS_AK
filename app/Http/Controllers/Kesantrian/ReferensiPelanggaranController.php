<?php

namespace App\Http\Controllers\Kesantrian;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kesantrian\StoreReferensiPelanggaranRequest;
use App\Kesantrian\KesantrianMenu;
use App\Models\ReferensiPelanggaran;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReferensiPelanggaranController extends Controller
{
    public function index(): View|RedirectResponse
    {
        try {
            $items = ReferensiPelanggaran::query()->orderBy('urut')->get();
        } catch (QueryException) {
            return $this->databaseUnavailable();
        }

        return view('kesantrian.master-pelanggaran', [
            'account' => auth()->user(),
            'groups' => KesantrianMenu::groups(),
            'current' => KesantrianMenu::find('master-pelanggaran'),
            'items' => $items,
        ]);
    }

    public function store(StoreReferensiPelanggaranRequest $request): RedirectResponse
    {
        try {
            ReferensiPelanggaran::query()->create([
                'jenis_pelangaran' => $request->validated('nama'),
                'skor' => $request->validated('skor'),
                'keterangan' => $request->note(),
            ]);
        } catch (QueryException) {
            return $this->databaseUnavailable();
        }

        return redirect()
            ->route('kesantrian.menu', 'master-pelanggaran')
            ->with('status', 'Pelanggaran ditambahkan.');
    }

    private function databaseUnavailable(): RedirectResponse
    {
        return redirect()
            ->route('kesantrian.home')
            ->withErrors([
                'database' => 'Tidak dapat terhubung ke database. Periksa koneksi, lalu coba lagi.',
            ]);
    }
}
