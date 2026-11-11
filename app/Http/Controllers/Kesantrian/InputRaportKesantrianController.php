<?php

namespace App\Http\Controllers\Kesantrian;

use App\Http\Controllers\Controller;
use App\Kesantrian\KesantrianMenu;
use App\Models\RaportKesantrian;
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
        $years = TahunAkademik::query()->orderBy('urut', 'desc')->get();
        $selectedYear = (string) $request->input('periode', $years->first()?->thn_aka ?? '');
        $selectedDate = (string) $request->input('tgl', now()->format('Y-m-d'));

        $previewRows = session('preview_rows', []);
        $previewFilename = session('preview_filename', '');

        // Recent 10 records already saved for the selected period (as recent preview)
        $recentSaved = [];
        if ($selectedYear !== '') {
            $recentSaved = DB::table('akademik_raport_kesantrian as r')
                ->leftJoin('scctcust as s', 'r.NOCUST', '=', 's.NOCUST')
                ->where('r.periode', $selectedYear)
                ->select([
                    'r.*',
                    's.NMCUST',
                ])
                ->orderBy('r.urut', 'desc')
                ->limit(10)
                ->get();
        }

        return view('kesantrian.input-raport-kesantrian', [
            'account' => auth()->user(),
            'groups' => KesantrianMenu::groups(),
            'current' => KesantrianMenu::find('input-raport-kesantrian'),
            'years' => $years,
            'selectedYear' => $selectedYear,
            'selectedDate' => $selectedDate,
            'previewRows' => $previewRows,
            'previewFilename' => $previewFilename,
            'recentSaved' => $recentSaved,
        ]);
    }

    public function preview(Request $request): RedirectResponse
    {
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
            $parsedRows = $this->spreadsheetService->parseFile($file);

            if (empty($parsedRows)) {
                return back()->with('error', 'Berkas kosong atau tidak ditemukan data santri pada baris berikutnya.')->withInput();
            }

            $enrichedRows = $this->spreadsheetService->enrichWithSantri($parsedRows);

            return redirect()
                ->route('kesantrian.menu', [
                    'menu' => 'input-raport-kesantrian',
                    'periode' => $request->input('periode'),
                    'tgl' => $request->input('tgl'),
                ])
                ->with('preview_rows', $enrichedRows)
                ->with('preview_filename', $file->getClientOriginalName())
                ->with('status', 'Berkas berhasil dibaca! Silakan periksa data preview sebelum menekan tombol Simpan.')
                ->withInput();
        } catch (Throwable $e) {
            return back()->with('error', 'Gagal memproses berkas: '.$e->getMessage())->withInput();
        }
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'periode' => ['required', 'string', 'max:50'],
            'tgl' => ['required', 'date'],
            'rows_json' => ['required', 'string'],
        ]);

        $rows = json_decode((string) $request->input('rows_json'), true);
        if (! is_array($rows) || empty($rows)) {
            return back()->with('error', 'Tidak ada data raport yang dapat disimpan. Silakan unggah dan preview berkas kembali.')->withInput();
        }

        $periode = trim((string) $request->input('periode'));
        $tgl = (string) $request->input('tgl');
        $savedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                $nis = trim((string) ($row['nis'] ?? ''));
                if ($nis === '') {
                    continue;
                }

                RaportKesantrian::query()->updateOrCreate(
                    [
                        'NOCUST' => $nis,
                        'periode' => $periode,
                    ],
                    [
                        'tgl' => $tgl,
                        'Catatan' => (string) ($row['catatan'] ?? ''),
                        'IbadahA' => (string) ($row['ibadah1'] ?? ''),
                        'IbadahB' => (string) ($row['ibadah2'] ?? ''),
                        'IbadahC' => (string) ($row['ibadah3'] ?? ''),
                        'IbadahD' => (string) ($row['ibadah4'] ?? ''),
                        'IbadahE' => (string) ($row['ibadah5'] ?? ''),
                        'AdabA' => (string) ($row['adab1'] ?? ''),
                        'AdabB' => (string) ($row['adab2'] ?? ''),
                        'AdabC' => (string) ($row['adab3'] ?? ''),
                        'AdabD' => (string) ($row['adab4'] ?? ''),
                        'AdabE' => (string) ($row['adab5'] ?? ''),
                        'KepribadianA' => (string) ($row['pribadi1'] ?? ''),
                        'KepribadianB' => (string) ($row['pribadi2'] ?? ''),
                        'KepribadianC' => (string) ($row['pribadi3'] ?? ''),
                        'KepribadianD' => (string) ($row['pribadi4'] ?? ''),
                        'KepribadianE' => (string) ($row['pribadi5'] ?? ''),
                        'PengembanganA' => (string) ($row['diri1'] ?? ''),
                        'PengembanganB' => (string) ($row['diri2'] ?? ''),
                        'PengembanganC' => (string) ($row['diri3'] ?? ''),
                    ]
                );

                $savedCount++;
            }

            DB::commit();

            return redirect()
                ->route('kesantrian.menu', [
                    'menu' => 'data-raport-kesantrian',
                    'periode' => $periode,
                ])
                ->with('status', "Berhasil menyimpan {$savedCount} data raport kesantrian untuk periode {$periode}.");
        } catch (Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Terjadi kesalahan saat menyimpan data: '.$e->getMessage())->withInput();
        }
    }

    public function template(Request $request): BinaryFileResponse|Response
    {
        $format = strtolower((string) $request->input('format', 'xlsx'));

        if ($format === 'csv') {
            $csv = $this->spreadsheetService->generateCsvTemplate();

            return response($csv, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="template_import_raport_kesantrian.csv"',
            ]);
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'tpl_').'.xlsx';
        $this->spreadsheetService->generateXlsxTemplate($tmpFile);

        return response()->download($tmpFile, 'template_import_raport_kesantrian.xlsx')->deleteFileAfterSend(true);
    }
}
