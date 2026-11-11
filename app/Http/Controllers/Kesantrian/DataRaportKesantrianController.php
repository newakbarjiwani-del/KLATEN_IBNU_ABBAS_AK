<?php

namespace App\Http\Controllers\Kesantrian;

use App\Http\Controllers\Controller;
use App\Kesantrian\KesantrianMenu;
use App\Models\RaportKesantrian;
use App\Models\TahunAkademik;
use App\Services\SpreadsheetFile;
use App\Tables\ColumnGroup;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class DataRaportKesantrianController extends Controller
{
    public function index(Request $request): View
    {
        $years = TahunAkademik::query()->orderBy('urut', 'desc')->get();

        $selectedPeriode = (string) $request->input('periode', '');
        $tglAwal = (string) $request->input('tgl_awal', '');
        $tglAkhir = (string) $request->input('tgl_akhir', '');
        $search = trim((string) $request->input('q', ''));
        $columnGroups = [
            'periode' => ['sql' => 'r.periode', 'label' => 'Tahun akademik'],
            'NOCUST' => ['sql' => 'r.NOCUST', 'label' => 'NIS'],
            'NMCUST' => ['sql' => 's.NMCUST', 'label' => 'Nama Siswa'],
            'kelompok' => ['sql' => 's.DESC03', 'label' => 'Kelompok'],
            'tgl' => ['sql' => 'r.tgl', 'label' => 'Tanggal'],
        ];
        $groupKey = ColumnGroup::key($request, $columnGroups);
        $records = ColumnGroup::order($this->filtered($request), $groupKey, $columnGroups)
            ->orderBy('r.urut', 'desc')
            ->paginate(25)
            ->withQueryString();

        return view('kesantrian.data-raport-kesantrian', [
            'account' => auth()->user(),
            'groups' => KesantrianMenu::groups(),
            'current' => KesantrianMenu::find('data-raport-kesantrian'),
            'years' => $years,
            'selectedPeriode' => $selectedPeriode,
            'tglAwal' => $tglAwal,
            'tglAkhir' => $tglAkhir,
            'search' => $search,
            'records' => $records,
            'groupKey' => $groupKey,
            'groupLabel' => ColumnGroup::label($groupKey, $columnGroups),
        ]);
    }

    public function export(Request $request, SpreadsheetFile $sheets): BinaryFileResponse
    {
        $records = $this->filtered($request)->orderBy('r.urut', 'desc')->get();
        $rows = [[
            'No', 'Tahun akademik', 'NIS', 'Nama', 'Kelompok', 'Tanggal',
            'Sholat Wajib', 'Sholat Dhuha', 'Sholat Lail', 'Puasa Sunnah', 'Sedekah',
            'Menghormati', 'Berkata Sopan', 'Jujur', 'Tolong Menolong', 'Mengajak Kebaikan',
            'Kebersihan Diri', 'Kebersihan Lingkungan', 'Hubungan Sosial', 'Disiplin', 'Kesehatan',
            'Pengembangan Diri', 'Aktif Organisasi', 'Literasi Buku', 'Wali Hujroh',
        ]];

        foreach ($records as $index => $row) {
            $rows[] = [
                $index + 1,
                (string) $row->periode,
                trim((string) $row->NOCUST),
                trim((string) $row->NMCUST),
                trim((string) $row->kelompok),
                (string) $row->tgl,
                (string) $row->IbadahA,
                (string) $row->IbadahB,
                (string) $row->IbadahC,
                (string) $row->IbadahD,
                (string) $row->IbadahE,
                (string) $row->AdabA,
                (string) $row->AdabB,
                (string) $row->AdabC,
                (string) $row->AdabD,
                (string) $row->AdabE,
                (string) $row->KepribadianA,
                (string) $row->KepribadianB,
                (string) $row->KepribadianC,
                (string) $row->KepribadianD,
                (string) $row->KepribadianE,
                (string) $row->PengembanganA,
                (string) $row->PengembanganB,
                (string) $row->PengembanganC,
                (string) $row->Catatan,
            ];
        }

        return $sheets->download('raport-kesantrian.xlsx', $rows, 'Raport');
    }

    private function filtered(Request $request): Builder
    {
        $selectedPeriode = (string) $request->input('periode', '');
        $tglAwal = (string) $request->input('tgl_awal', '');
        $tglAkhir = (string) $request->input('tgl_akhir', '');
        $search = trim((string) $request->input('q', ''));
        $like = '%'.addcslashes($search, '%_\\').'%';

        return DB::table('akademik_raport_kesantrian as r')
            ->leftJoin('scctcust as s', 'r.NOCUST', '=', 's.NOCUST')
            ->select([
                'r.urut',
                'r.NOCUST',
                'r.tgl',
                'r.periode',
                'r.Catatan',
                'r.IbadahA',
                'r.IbadahB',
                'r.IbadahC',
                'r.IbadahD',
                'r.IbadahE',
                'r.AdabA',
                'r.AdabB',
                'r.AdabC',
                'r.AdabD',
                'r.AdabE',
                'r.KepribadianA',
                'r.KepribadianB',
                'r.KepribadianC',
                'r.KepribadianD',
                'r.KepribadianE',
                'r.PengembanganA',
                'r.PengembanganB',
                'r.PengembanganC',
                's.NMCUST',
                's.DESC03 as kelompok',
            ])
            ->when($selectedPeriode !== '', fn (Builder $query) => $query->where('r.periode', $selectedPeriode))
            ->when($tglAwal !== '', fn (Builder $query) => $query->where('r.tgl', '>=', $tglAwal))
            ->when($tglAkhir !== '', fn (Builder $query) => $query->where('r.tgl', '<=', $tglAkhir))
            ->when($search !== '', function (Builder $query) use ($like): void {
                $query->where(function (Builder $inner) use ($like): void {
                    $inner->where('r.NOCUST', 'like', $like)
                        ->orWhere('s.NMCUST', 'like', $like);
                });
            });
    }

    public function destroy(int $urut): RedirectResponse
    {
        try {
            $deleted = RaportKesantrian::query()->where('urut', $urut)->delete();

            if ($deleted) {
                return back()->with('status', 'Data raport santri berhasil dihapus.');
            }

            return back()->with('error', 'Data raport tidak ditemukan atau telah dihapus.');
        } catch (Throwable $e) {
            return back()->with('error', 'Gagal menghapus data: '.$e->getMessage());
        }
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return back()->with('error', 'Tidak ada data yang dipilih untuk dihapus.');
        }

        try {
            $count = RaportKesantrian::query()->whereIn('urut', $ids)->delete();

            return back()->with('status', "Berhasil menghapus {$count} data raport santri terpilih.");
        } catch (Throwable $e) {
            return back()->with('error', 'Gagal menghapus data: '.$e->getMessage());
        }
    }
}
