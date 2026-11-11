<?php

namespace App\Http\Controllers\Kesantrian;

use App\Http\Controllers\Controller;
use App\Kesantrian\KesantrianMenu;
use App\Models\Santri;
use App\Models\TahunAkademik;
use App\Tables\ColumnGroup;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DataPelanggaranController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        try {
            $years = TahunAkademik::query()->orderByDesc('urut')->get();
            $classes = Santri::daftarKelas();

            $selectedYear = trim((string) $request->query('thn_aka', ''));
            $selectedClass = trim((string) $request->query('kelas', ''));
            $search = trim((string) $request->query('q', ''));

            $query = DB::table('akademik_pelanggaran as p')
                ->leftJoin('scctcust as c', 'c.NOCUST', '=', 'p.NOCUST')
                ->select(
                    'p.NOCUST',
                    'c.NMCUST',
                    'c.DESC02 as kelas',
                    'p.thn_aka',
                    DB::raw('COALESCE(SUM(p.skor_kredit), 0) as total_pelanggaran'),
                    DB::raw('COALESCE(SUM(p.skor_debet), 0) as total_remisi'),
                    DB::raw('COALESCE(SUM(p.skor_kredit), 0) - COALESCE(SUM(p.skor_debet), 0) as total_skor'),
                    DB::raw('COUNT(p.urut) as total_catatan')
                )
                ->groupBy('p.NOCUST', 'c.NMCUST', 'c.DESC02', 'p.thn_aka');

            if ($selectedYear !== '') {
                $query->where('p.thn_aka', $selectedYear);
            }

            if ($selectedClass !== '') {
                $query->where('c.DESC02', $selectedClass);
            }

            if ($search !== '') {
                $query->where(function ($q) use ($search): void {
                    $q->where('p.NOCUST', 'like', "%{$search}%")
                        ->orWhere('c.NMCUST', 'like', "%{$search}%");
                });
            }

            $columnGroups = [
                'NOCUST' => ['sql' => 'p.NOCUST', 'label' => 'NIS'],
                'NMCUST' => ['sql' => 'c.NMCUST', 'label' => 'Nama Siswa'],
                'kelas' => ['sql' => 'c.DESC02', 'label' => 'Kelas'],
                'total_pelanggaran' => ['sql' => 'total_pelanggaran', 'label' => 'Pelanggaran'],
                'total_remisi' => ['sql' => 'total_remisi', 'label' => 'Remisi'],
                'total_skor' => ['sql' => 'total_skor', 'label' => 'Total'],
                'thn_aka' => ['sql' => 'p.thn_aka', 'label' => 'Thn Akademik'],
            ];
            $groupKey = ColumnGroup::key($request, $columnGroups);
            $records = ColumnGroup::order($query, $groupKey, $columnGroups)
                ->orderByDesc('total_skor')
                ->orderBy('c.NMCUST')
                ->paginate(30)
                ->withQueryString();
        } catch (QueryException) {
            return $this->databaseUnavailable();
        }

        return view('kesantrian.data-pelanggaran', [
            'account' => auth()->user(),
            'groups' => KesantrianMenu::groups(),
            'current' => KesantrianMenu::find('data-pelanggaran'),
            'years' => $years,
            'classes' => $classes,
            'selectedYear' => $selectedYear,
            'selectedClass' => $selectedClass,
            'search' => $search,
            'records' => $records,
            'groupKey' => $groupKey,
            'groupLabel' => ColumnGroup::label($groupKey, $columnGroups),
        ]);
    }

    public function detail(Request $request): JsonResponse
    {
        $nis = trim((string) $request->query('nis', ''));
        $thnAka = trim((string) $request->query('thn', ''));

        if ($nis === '') {
            return response()->json(['message' => 'NIS tidak valid.'], 422);
        }

        try {
            $santri = Santri::query()->where('NOCUST', $nis)->first();

            $items = DB::table('akademik_pelanggaran as p')
                ->leftJoin('akademik_ref_langgar as r', function ($join): void {
                    $join->on('r.urut', '=', 'p.id_pelanggaran')
                        ->orOn('r.urut', '=', 'p.id_remisi');
                })
                ->where('p.NOCUST', $nis)
                ->when($thnAka !== '', fn ($q) => $q->where('p.thn_aka', $thnAka))
                ->orderByDesc('p.tgl_kejadian')
                ->orderByDesc('p.urut')
                ->get([
                    'p.urut',
                    'p.tgl_kejadian',
                    'p.skor_kredit',
                    'p.skor_debet',
                    'p.id_pelanggaran',
                    'p.id_remisi',
                    'p.keterangan',
                    'p.thn_aka',
                    'r.jenis_pelangaran',
                ]);
        } catch (QueryException) {
            return response()->json(['message' => 'Tidak dapat terhubung ke database.'], 503);
        }

        return response()->json([
            'nis' => $nis,
            'nama' => $santri?->nama() ?: $nis,
            'kelas' => trim((string) ($santri?->DESC02 ?? '')),
            'thn_aka' => $thnAka,
            'total_pelanggaran' => (int) $items->sum('skor_kredit'),
            'total_remisi' => (int) $items->sum('skor_debet'),
            'total_skor' => (int) ($items->sum('skor_kredit') - $items->sum('skor_debet')),
            'items' => $items->map(fn ($item) => [
                'urut' => $item->urut,
                'tanggal' => $item->tgl_kejadian ? Carbon::parse($item->tgl_kejadian)->format('d/m/Y') : '-',
                'jenis' => $item->id_pelanggaran !== null ? 'Pelanggaran' : 'Remisi',
                'pelanggaran' => $item->jenis_pelangaran ?? ($item->id_pelanggaran !== null ? 'Pelanggaran' : 'Remisi'),
                'skor_kredit' => $item->id_pelanggaran !== null ? (string) $item->skor_kredit : '',
                'skor_debet' => $item->id_remisi !== null ? (string) $item->skor_debet : '',
                'keterangan' => $item->keterangan ?? '-',
                'thn_aka' => $item->thn_aka,
            ]),
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
