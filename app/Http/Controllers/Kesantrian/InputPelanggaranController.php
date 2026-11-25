<?php

namespace App\Http\Controllers\Kesantrian;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kesantrian\StoreInputPelanggaranRequest;
use App\Kesantrian\KesantrianMenu;
use App\Models\CatatanPelanggaran;
use App\Models\ReferensiPelanggaran;
use App\Models\Santri;
use App\Models\TahunAkademik;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InputPelanggaranController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        try {
            $years = TahunAkademik::query()->orderByDesc('urut')->get();
            $unit = trim((string) auth()->user()?->unit);
            $references = ReferensiPelanggaran::query()->orderBy('urut');

            if (in_array($unit, ReferensiPelanggaran::UNITS, true)) {
                $references->where('unit', $unit);
            }

            $references = $references->get();

            $selectedNis = trim((string) $request->query('nis', ''));
            $selectedSantri = null;
            $records = collect();
            $totals = ['pelanggaran' => 0, 'remisi' => 0, 'sisa' => 0];

            if ($selectedNis !== '') {
                $selectedSantri = Santri::query()->where('NOCUST', $selectedNis)->first();

                $records = DB::table('akademik_pelanggaran as p')
                    ->leftJoin('akademik_ref_langgar as r', function ($join): void {
                        $join->on('r.urut', '=', 'p.id_pelanggaran')
                            ->orOn('r.urut', '=', 'p.id_remisi');
                    })
                    ->leftJoin('scctcust as c', 'c.NOCUST', '=', 'p.NOCUST')
                    ->where('p.NOCUST', $selectedNis)
                    ->orderByDesc('p.urut')
                    ->get([
                        'p.urut',
                        'p.NOCUST',
                        'p.tgl_kejadian',
                        'p.skor_debet',
                        'p.skor_kredit',
                        'p.id_pelanggaran',
                        'p.id_remisi',
                        'p.keterangan',
                        'p.thn_aka',
                        'r.jenis_pelangaran',
                        'c.NMCUST',
                    ]);

                $totalPelanggaran = (int) $records->sum('skor_kredit');
                $totalRemisi = (int) $records->sum('skor_debet');
                $totals = [
                    'pelanggaran' => $totalPelanggaran,
                    'remisi' => $totalRemisi,
                    'sisa' => $totalPelanggaran - $totalRemisi,
                ];
            }
        } catch (QueryException) {
            return $this->databaseUnavailable();
        }

        return view('kesantrian.input-pelanggaran', [
            'account' => auth()->user(),
            'groups' => KesantrianMenu::groups(),
            'current' => KesantrianMenu::find('input-pelanggaran'),
            'years' => $years,
            'references' => $references,
            'records' => $records,
            'selectedNis' => $selectedNis,
            'selectedSantri' => $selectedSantri,
            'totals' => $totals,
        ]);
    }

    public function store(StoreInputPelanggaranRequest $request): RedirectResponse
    {
        $reference = ReferensiPelanggaran::query()->findOrFail($request->integer('id_langgar'));
        $violation = $request->string('jenis')->toString() === 'pelanggaran';
        $score = $violation ? $reference->skor : $request->integer('skor');
        $nocust = $request->string('nocust')->toString();

        try {
            CatatanPelanggaran::query()->create([
                'NOCUST' => $nocust,
                'tgl_kejadian' => $request->date('tgl_kejadian')->toDateString(),
                'skor_debet' => $violation ? null : $score,
                'skor_kredit' => $violation ? $score : null,
                'id_pelanggaran' => $violation ? $reference->urut : null,
                'id_remisi' => $violation ? null : $reference->urut,
                'keterangan' => $request->note(),
                'thn_aka' => $request->string('thn_aka')->toString(),
            ]);
        } catch (QueryException) {
            return $this->databaseUnavailable();
        }

        return redirect()
            ->route('kesantrian.menu', 'input-pelanggaran')
            ->with('status', $violation ? 'Pelanggaran disimpan.' : 'Remisi disimpan.');
    }

    public function santri(Request $request): JsonResponse
    {
        $nis = trim((string) $request->query('nis', ''));

        if ($nis === '' || strlen($nis) > 10) {
            return response()->json(['message' => 'NIS tidak ditemukan.'], 422);
        }

        try {
            $santri = Santri::query()->where('NOCUST', $nis)->first();
        } catch (QueryException) {
            return response()->json(['message' => 'Tidak dapat terhubung ke database. Periksa koneksi, lalu coba lagi.'], 503);
        }

        if ($santri === null) {
            return response()->json(['message' => 'NIS tidak ditemukan.'], 404);
        }

        $records = DB::table('akademik_pelanggaran as p')
            ->leftJoin('akademik_ref_langgar as r', function ($join): void {
                $join->on('r.urut', '=', 'p.id_pelanggaran')
                    ->orOn('r.urut', '=', 'p.id_remisi');
            })
            ->where('p.NOCUST', $nis)
            ->orderByDesc('p.urut')
            ->get([
                'p.urut',
                'p.tgl_kejadian',
                'p.skor_debet',
                'p.skor_kredit',
                'p.id_pelanggaran',
                'p.id_remisi',
                'p.keterangan',
                'p.thn_aka',
                'r.jenis_pelangaran',
            ]);

        return response()->json([
            'nama' => $santri->nama(),
            'nis' => trim((string) $santri->NOCUST),
            'records' => $records->map(fn ($r) => [
                'urut' => $r->urut,
                'nocust' => $nis,
                'nmcust' => $santri->nama(),
                'tanggal' => $r->tgl_kejadian ? Carbon::parse($r->tgl_kejadian)->format('d/m/Y') : '',
                'thn_aka' => $r->thn_aka ?? '',
                'jenis_pelangaran' => $r->jenis_pelangaran ?? '',
                'skor_kredit' => $r->id_pelanggaran !== null ? (string) $r->skor_kredit : '',
                'skor_debet' => $r->id_remisi !== null ? (string) $r->skor_debet : '',
                'keterangan' => $r->keterangan ?? '',
            ]),
            'totals' => [
                'pelanggaran' => (int) $records->sum('skor_kredit'),
                'remisi' => (int) $records->sum('skor_debet'),
                'sisa' => (int) ($records->sum('skor_kredit') - $records->sum('skor_debet')),
            ],
        ]);
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
