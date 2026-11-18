<?php

namespace App\Http\Controllers\Kesantrian;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kesantrian\StoreTahunAkademikRequest;
use App\Kesantrian\KesantrianMenu;
use App\Models\TahunAkademik;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TahunAkademikController extends Controller
{
    public function index(): View|RedirectResponse
    {
        try {
            $years = TahunAkademik::query()->orderByDesc('thn_aka')->get();
        } catch (QueryException) {
            return $this->databaseUnavailable();
        }

        return view('kesantrian.tahun-akademik', [
            'account' => auth()->user(),
            'groups' => KesantrianMenu::groups(),
            'current' => KesantrianMenu::find('tahun-akademik'),
            'years' => $years,
        ]);
    }

    public function store(StoreTahunAkademikRequest $request): RedirectResponse
    {
        $label = $request->label();

        try {
            TahunAkademik::query()->create([
                'thn_aka' => $label,
            ]);
        } catch (QueryException) {
            return $this->databaseUnavailable();
        }

        return redirect()
            ->route('kesantrian.menu', 'tahun-akademik')
            ->with('status', 'Tahun akademik '.$label.' ditambahkan.');
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
