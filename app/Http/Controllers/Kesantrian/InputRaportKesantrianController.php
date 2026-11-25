<?php

namespace App\Http\Controllers\Kesantrian;

use App\Http\Controllers\Controller;
use App\Kesantrian\KesantrianMenu;
use App\Kesantrian\UnitRaport;
use App\Models\KategoriRaportKesantrian;
use App\Models\NilaiRaportKesantrian;
use App\Models\RaportKesantrian;
use App\Models\ReferensiPelanggaran;
use App\Models\Santri;
use App\Models\TahunAkademik;
use App\Services\RaportKesantrianSpreadsheetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class InputRaportKesantrianController extends Controller
{
    public function __construct(
        protected RaportKesantrianSpreadsheetService $spreadsheetService
    ) {}

    public function index(Request $request): View
    {
        $unit = UnitRaport::dipilih($request->query('unit'));
        $kategori = KategoriRaportKesantrian::untuk($unit);
        $years = TahunAkademik::query()->orderBy('urut', 'desc')->get();
        $selectedYear = (string) $request->input('periode', $years->first()?->thn_aka ?? '');
        $selectedDate = (string) $request->input('tgl', now()->format('Y-m-d'));
        $previewRows = session('preview_unit') === $unit ? session('preview_rows', []) : [];

        $recentSaved = collect();
        if ($selectedYear !== '' && $kategori->contains(fn ($kelompok) => $kelompok->items->isNotEmpty())) {
            $recentSaved = UnitRaport::batasi(
                DB::table('akademik_raport_kesantrian as r')
                    ->leftJoin('scctcust as s', 'r.NOCUST', '=', 's.NOCUST')
                    ->where('r.periode', $selectedYear),
                's.CODE02',
                $unit,
            )
                ->select(['r.urut', 'r.NOCUST', 'r.tgl', 'r.periode', 'r.Catatan', 's.NMCUST'])
                ->orderBy('r.urut', 'desc')
                ->limit(10)
                ->get();
            $this->lampirkanNilai($recentSaved);
        }

        return view('kesantrian.input-raport-kesantrian', [
            'account' => auth()->user(),
            'groups' => KesantrianMenu::groups(),
            'current' => KesantrianMenu::find('input-raport-kesantrian'),
            'years' => $years,
            'selectedYear' => $selectedYear,
            'selectedDate' => $selectedDate,
            'previewRows' => $previewRows,
            'previewFilename' => session('preview_unit') === $unit ? session('preview_filename', '') : '',
            'recentSaved' => $recentSaved,
            'kategori' => $kategori,
            'units' => ReferensiPelanggaran::UNITS,
            'selectedUnit' => $unit,
            'lockedUnit' => UnitRaport::terkunci(),
        ]);
    }

    public function preview(Request $request): RedirectResponse
    {
        $unit = UnitRaport::dipilih($request->input('unit'));
        $items = $this->butir($unit);
        if ($items === []) {
            return $this->belumAdaKategori($unit);
        }

        $request->validate([
            'file' => ['required', 'file', 'max:10240'],
            'periode' => ['nullable', 'string', 'max:50'],
            'tgl' => ['nullable', 'date'],
        ]);

        $file = $request->file('file');
        $extension = strtolower((string) $file->getClientOriginalExtension());
        if (! in_array($extension, ['xlsx', 'csv', 'txt'], true)) {
            return back()->with('error', 'Format berkas tidak didukung. Harap unggah berkas berekstensi .xlsx atau .csv')->withInput();
        }

        try {
            $parsedRows = $this->spreadsheetService->parseFile($file, $items);

            if (empty($parsedRows)) {
                return back()->with('error', 'Berkas kosong atau tidak ditemukan data santri pada baris berikutnya.')->withInput();
            }

            $enrichedRows = $this->spreadsheetService->enrichWithSantri($parsedRows, $unit);

            return redirect()
                ->route('kesantrian.menu', [
                    'menu' => 'input-raport-kesantrian',
                    'periode' => $request->input('periode'),
                    'tgl' => $request->input('tgl'),
                    'unit' => $unit,
                ])
                ->with('preview_rows', $enrichedRows)
                ->with('preview_filename', $file->getClientOriginalName())
                ->with('preview_unit', $unit)
                ->with('status', 'Berkas berhasil dibaca. Periksa data preview sebelum menyimpan.')
                ->withInput();
        } catch (Throwable $e) {
            return back()->with('error', 'Gagal memproses berkas: '.$e->getMessage())->withInput();
        }
    }

    public function store(Request $request): RedirectResponse
    {
        $unit = UnitRaport::dipilih($request->input('unit'));
        $items = $this->butir($unit);
        if ($items === []) {
            return $this->belumAdaKategori($unit);
        }

        $request->validate([
            'periode' => ['required', 'string', 'max:50'],
            'tgl' => ['required', 'date'],
            'rows_json' => ['required', 'string'],
        ]);

        $rows = json_decode((string) $request->input('rows_json'), true);
        if (! is_array($rows) || $rows === []) {
            return back()->with('error', 'Tidak ada data raport yang dapat disimpan. Silakan unggah dan preview berkas kembali.')->withInput();
        }

        $allowed = array_fill_keys(array_column($items, 'id'), true);
        $periode = trim((string) $request->input('periode'));
        $tgl = (string) $request->input('tgl');
        $savedCount = 0;
        $skippedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                $nis = trim((string) ($row['nis'] ?? ''));
                $santri = $nis === '' ? null : Santri::query()->where('NOCUST', $nis)->first();
                $santriUnit = ReferensiPelanggaran::canonicalUnit((string) $santri?->CODE02);

                if ($santri === null || $santriUnit !== $unit) {
                    $skippedCount++;

                    continue;
                }

                $raport = RaportKesantrian::query()->updateOrCreate(
                    [
                        'NOCUST' => $nis,
                        'periode' => $periode,
                    ],
                    [
                        'tgl' => $tgl,
                        'Catatan' => (string) ($row['catatan'] ?? ''),
                    ]
                );

                foreach ((array) ($row['nilai'] ?? []) as $itemId => $nilai) {
                    $itemId = (int) $itemId;
                    if (! isset($allowed[$itemId])) {
                        continue;
                    }

                    NilaiRaportKesantrian::query()->updateOrCreate(
                        [
                            'raport_urut' => $raport->urut,
                            'item_id' => $itemId,
                        ],
                        [
                            'nilai' => mb_substr(trim((string) $nilai), 0, 255),
                        ]
                    );
                }

                $savedCount++;
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Terjadi kesalahan saat menyimpan data: '.$e->getMessage())->withInput();
        }

        if ($savedCount === 0) {
            return back()->with('error', 'Tidak ada baris yang disimpan. Santri harus terdaftar pada unit '.$unit.'.')->withInput();
        }

        return redirect()
            ->route('kesantrian.menu', [
                'menu' => 'data-raport-kesantrian',
                'periode' => $periode,
                'unit' => $unit,
            ])
            ->with('status', "Berhasil menyimpan {$savedCount} data raport kesantrian. {$skippedCount} baris dilewati.");
    }

    public function template(Request $request): BinaryFileResponse|Response|RedirectResponse
    {
        $unit = UnitRaport::dipilih($request->query('unit'));
        $items = $this->butir($unit);
        if ($items === []) {
            return $this->belumAdaKategori($unit);
        }

        $format = strtolower((string) $request->input('format', 'xlsx'));

        if ($format === 'csv') {
            $csv = $this->spreadsheetService->generateCsvTemplate($items);

            return response($csv, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="template_import_raport_kesantrian.csv"',
            ]);
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'tpl_').'.xlsx';
        $this->spreadsheetService->generateXlsxTemplate($tmpFile, $items);

        return response()->download($tmpFile, 'template_import_raport_kesantrian.xlsx')->deleteFileAfterSend(true);
    }

    /**
     * @return list<array{id: int, nama: string}>
     */
    private function butir(string $unit): array
    {
        $items = [];
        foreach (KategoriRaportKesantrian::untuk($unit) as $kategori) {
            foreach ($kategori->items as $item) {
                $items[] = ['id' => $item->id, 'nama' => $item->nama];
            }
        }

        return $items;
    }

    private function belumAdaKategori(string $unit): RedirectResponse
    {
        return redirect()
            ->route('kesantrian.menu', ['menu' => 'input-raport-kesantrian', 'unit' => $unit])
            ->with('error', 'Buat kategori dan butir raport untuk unit ini terlebih dahulu.');
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
