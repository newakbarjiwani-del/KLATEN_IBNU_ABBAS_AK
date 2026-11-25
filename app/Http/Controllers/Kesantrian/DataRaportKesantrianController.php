<?php

namespace App\Http\Controllers\Kesantrian;

use App\Http\Controllers\Controller;
use App\Kesantrian\KesantrianMenu;
use App\Kesantrian\UnitRaport;
use App\Models\KategoriRaportKesantrian;
use App\Models\NilaiRaportKesantrian;
use App\Models\RaportKesantrian;
use App\Models\ReferensiPelanggaran;
use App\Models\TahunAkademik;
use App\Services\SpreadsheetFile;
use App\Tables\ColumnGroup;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class DataRaportKesantrianController extends Controller
{
    public function index(Request $request): View
    {
        $unit = UnitRaport::dipilih($request->query('unit'));
        $kategori = KategoriRaportKesantrian::untuk($unit);
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
        $records = ColumnGroup::order($this->filtered($request, $unit), $groupKey, $columnGroups)
            ->orderBy('r.urut', 'desc')
            ->paginate(25)
            ->withQueryString();
        $this->lampirkanNilai($records->getCollection());

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
            'kategori' => $kategori,
            'units' => ReferensiPelanggaran::UNITS,
            'selectedUnit' => $unit,
            'lockedUnit' => UnitRaport::terkunci(),
        ]);
    }

    public function export(Request $request, SpreadsheetFile $sheets): BinaryFileResponse|RedirectResponse
    {
        $unit = UnitRaport::dipilih($request->query('unit'));
        $kategori = KategoriRaportKesantrian::untuk($unit);
        $items = $kategori->flatMap(fn (KategoriRaportKesantrian $kelompok) => $kelompok->items);
        if ($items->isEmpty()) {
            return redirect()
                ->route('kesantrian.menu', ['menu' => 'data-raport-kesantrian', 'unit' => $unit])
                ->with('error', 'Buat kategori dan butir raport untuk unit ini terlebih dahulu.');
        }

        $records = $this->filtered($request, $unit)->orderBy('r.urut', 'desc')->get();
        $this->lampirkanNilai($records);
        $header = ['No', 'Tahun akademik', 'NIS', 'Nama', 'Kelompok', 'Tanggal'];
        foreach ($items as $item) {
            $header[] = $item->nama;
        }
        $header[] = 'Catatan';
        $rows = [$header];

        foreach ($records as $index => $row) {
            $line = [
                $index + 1,
                (string) $row->periode,
                trim((string) $row->NOCUST),
                trim((string) $row->NMCUST),
                trim((string) $row->kelompok),
                (string) $row->tgl,
            ];
            foreach ($items as $item) {
                $line[] = (string) ($row->nilai[$item->id] ?? '');
            }
            $line[] = (string) $row->Catatan;
            $rows[] = $line;
        }

        return $sheets->download('raport-kesantrian.xlsx', $rows, 'Raport');
    }

    public function cetak(Request $request, int $urut): Response
    {
        $unit = UnitRaport::dipilih($request->query('unit'));
        $row = $this->filtered($request, $unit)
            ->addSelect(['s.DESC02 as kelas'])
            ->where('r.urut', $urut)
            ->first();
        abort_if($row === null, 404);

        $kategori = KategoriRaportKesantrian::untuk($unit)
            ->filter(fn (KategoriRaportKesantrian $kelompok): bool => $kelompok->items->isNotEmpty())
            ->values();
        $this->lampirkanNilai(collect([$row]));
        $periode = $this->pecahPeriode((string) $row->periode);
        $berkas = 'laporan-akhlak-'.preg_replace('/[^\p{L}\p{N}_-]+/u', '', trim((string) $row->NOCUST)).'.html';

        $html = view('kesantrian.raport-kesantrian-cetak', [
            'row' => $row,
            'halamanPertama' => $kategori->take(3),
            'halamanKedua' => $kategori->slice(3)->values(),
            'periode' => $periode,
            'tanggalTtd' => $this->tanggalIndonesia($row->tgl),
            'logo' => $this->gambar('kesantrian-putri.png'),
            'kop' => $this->gambar('raport-kesantrian-kop.png'),
            'stempel' => $this->gambar('raport-kesantrian-stempel.png'),
            'tandaTangan' => $this->gambar('raport-kesantrian-ttd.png'),
        ])->render();

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$berkas.'"',
        ]);
    }

    private function filtered(Request $request, string $unit): Builder
    {
        $selectedPeriode = (string) $request->input('periode', '');
        $tglAwal = (string) $request->input('tgl_awal', '');
        $tglAkhir = (string) $request->input('tgl_akhir', '');
        $search = trim((string) $request->input('q', ''));
        $like = '%'.addcslashes($search, '%_\\').'%';

        return UnitRaport::batasi(
            DB::table('akademik_raport_kesantrian as r')
                ->leftJoin('scctcust as s', 'r.NOCUST', '=', 's.NOCUST'),
            's.CODE02',
            $unit,
        )
            ->select([
                'r.urut',
                'r.NOCUST',
                'r.tgl',
                'r.periode',
                'r.Catatan',
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
        $unit = UnitRaport::dipilih(request()->input('unit'));

        try {
            $owned = $this->filtered(request(), $unit)->where('r.urut', $urut)->exists();
            if (! $owned) {
                return back()->with('error', 'Data raport tidak ditemukan atau telah dihapus.');
            }

            NilaiRaportKesantrian::query()->where('raport_urut', $urut)->delete();
            RaportKesantrian::query()->where('urut', $urut)->delete();

            return back()->with('status', 'Data raport santri berhasil dihapus.');
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
            $unit = UnitRaport::dipilih($request->input('unit'));
            $owned = $this->filtered($request, $unit)->whereIn('r.urut', $ids)->pluck('r.urut');
            NilaiRaportKesantrian::query()->whereIn('raport_urut', $owned)->delete();
            $count = RaportKesantrian::query()->whereIn('urut', $owned)->delete();

            return back()->with('status', "Berhasil menghapus {$count} data raport santri terpilih.");
        } catch (Throwable $e) {
            return back()->with('error', 'Gagal menghapus data: '.$e->getMessage());
        }
    }

    /**
     * @return array{tahun: string, semester: string}
     */
    private function pecahPeriode(string $periode): array
    {
        $periode = trim($periode);

        if (preg_match('/^(\d{4})\s*\/\s*(\d{4})\s*[-–]\s*(.+)$/u', $periode, $cocok) === 1) {
            return [
                'tahun' => $cocok[1].'-'.$cocok[2],
                'semester' => mb_convert_case(mb_strtolower(trim($cocok[3])), MB_CASE_TITLE, 'UTF-8'),
            ];
        }

        return [
            'tahun' => $periode,
            'semester' => '',
        ];
    }

    private function tanggalIndonesia(mixed $value): string
    {
        if (! filled($value)) {
            return '';
        }

        $tanggal = Carbon::parse($value);
        $bulan = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        return $tanggal->day.' '.$bulan[$tanggal->month].' '.$tanggal->year;
    }

    private function gambar(string $berkas): string
    {
        $path = public_path($berkas);

        if (! is_file($path)) {
            return '';
        }

        $isi = file_get_contents($path);

        if ($isi === false) {
            return '';
        }

        return 'data:image/png;base64,'.base64_encode($isi);
    }

    private function lampirkanNilai(mixed $rows): void
    {
        $ids = collect($rows)->pluck('urut')->filter()->all();
        if ($ids === []) {
            return;
        }

        $nilai = [];
        foreach (NilaiRaportKesantrian::query()->whereIn('raport_urut', $ids)->get() as $row) {
            $nilai[$row->raport_urut][$row->item_id] = $row->nilai;
        }

        foreach ($rows as $row) {
            $row->nilai = $nilai[$row->urut] ?? [];
        }
    }
}
