<?php

namespace App\Http\Controllers\Akademik;

use App\Akademik\AkademikMenu;
use App\Http\Controllers\Controller;
use App\Http\Requests\Akademik\StoreRaportAkademikRequest;
use App\Models\RaportAkademik;
use App\Models\Santri;
use App\Models\TahunAkademik;
use App\Services\RaportFileStorage;
use App\Services\RaportStorageException;
use App\Services\SpreadsheetFile;
use App\Tables\ColumnGroup;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RaportAkademikController extends Controller
{
    public function __construct(private RaportFileStorage $files) {}

    public function upload(): View|RedirectResponse
    {
        try {
            $years = TahunAkademik::query()->orderByDesc('urut')->get();
            $classes = Santri::daftarUnitKelas();
        } catch (QueryException) {
            return $this->databaseUnavailable();
        }

        return view('akademik.upload-raport', [
            'account' => auth()->user(),
            'groups' => AkademikMenu::groups(),
            'current' => AkademikMenu::find('upload-raport'),
            'years' => $years,
            'classes' => $classes,
        ]);
    }

    public function data(Request $request): View|RedirectResponse
    {
        try {
            $years = TahunAkademik::query()->orderByDesc('urut')->get();
            $classes = Santri::daftarUnitKelas();

            $selectedYear = trim((string) $request->query('thn_aka', ''));
            $selectedClass = (string) $request->query('kelas', '');
            $search = mb_substr(trim((string) $request->query('q', '')), 0, 70);
            $groups = [
                'NOCUST' => ['sql' => 'r.NOCUST', 'label' => 'NIS'],
                'NMCUST' => ['sql' => 'c.NMCUST', 'label' => 'Nama siswa'],
                'jenjang' => ['sql' => 'c.CODE02', 'label' => 'Jenjang'],
                'kelas' => ['sql' => 'c.DESC02', 'label' => 'Kelas'],
                'kelompok' => ['sql' => 'c.DESC03', 'label' => 'Kelompok'],
                'THN_AKA' => ['sql' => 'r.THN_AKA', 'label' => 'Tahun akademik'],
                'UJIAN' => ['sql' => 'r.UJIAN', 'label' => 'Ujian'],
            ];
            $groupKey = ColumnGroup::key($request, $groups);
            $records = ColumnGroup::order($this->filteredRaport($request), $groupKey, $groups)
                ->orderByDesc('r.urut')
                ->paginate(30)
                ->withQueryString();

            $records->getCollection()->transform(function (object $record): object {
                $record->tautan = $this->files->url(RaportFileStorage::AKADEMIK, $record->FILE_RAPOT);
                $record->tersedia = $record->tautan !== null;
                $record->tanggal_upload = $this->tanggalUpload($record->tgl_upload ?? null);

                return $record;
            });
        } catch (QueryException) {
            return $this->databaseUnavailable();
        }

        return view('akademik.data-raport', [
            'account' => auth()->user(),
            'groups' => AkademikMenu::groups(),
            'current' => AkademikMenu::find('data-raport'),
            'years' => $years,
            'classes' => $classes,
            'selectedYear' => $selectedYear,
            'selectedClass' => $selectedClass,
            'search' => $search,
            'records' => $records,
            'groupKey' => $groupKey,
            'groupLabel' => ColumnGroup::label($groupKey, $groups),
        ]);
    }

    public function store(StoreRaportAkademikRequest $request): RedirectResponse
    {
        $filename = $this->files->filename($request->file('berkas')->getClientOriginalName());

        try {
            $nis = $request->string('nocust')->toString();
            $year = $request->string('thn_aka')->toString();
            $exam = $request->string('ujian')->toString();
            $filename = $this->files->store(
                RaportFileStorage::AKADEMIK,
                $request->file('berkas'),
                $filename,
                $nis,
                $year,
                $exam,
            );
            $this->simpanBaris(RaportAkademik::class, $nis, $year, $exam, $filename, $this->tanggalDariPermintaan($request));
        } catch (RaportStorageException $exception) {
            return back()
                ->withErrors(['berkas' => $exception->getMessage()])
                ->withInput();
        } catch (QueryException) {
            return $this->databaseUnavailable();
        }

        return redirect()
            ->route('akademik.menu', 'upload-raport')
            ->with('status', 'Data berhasil diunggah.');
    }

    public function suggest(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));

        if ($term === '' || mb_strlen($term) > 70) {
            return response()->json(['items' => []]);
        }

        $like = '%'.addcslashes($term, '%_\\').'%';

        try {
            $items = Santri::query()
                ->where(function ($query) use ($like): void {
                    $query->where('NOCUST', 'like', $like)
                        ->orWhere('NMCUST', 'like', $like);
                })
                ->orderBy('NOCUST')
                ->limit(8)
                ->get(['NOCUST', 'NMCUST', 'CODE02', 'DESC02', 'DESC03']);
        } catch (QueryException) {
            return response()->json(['message' => 'Tidak dapat terhubung ke database. Periksa koneksi, lalu coba lagi.'], 503);
        }

        return response()->json([
            'items' => $items->map(function (Santri $santri): array {
                $unit = trim((string) $santri->CODE02);
                $kelas = trim((string) $santri->DESC02);
                $kelompok = trim((string) $santri->DESC03);

                return [
                    'nis' => trim((string) $santri->NOCUST),
                    'nama' => $santri->nama(),
                    'kelas_kunci' => Santri::kunciUnitKelas($unit, $kelas, $kelompok),
                    'kelas_label' => Santri::labelUnitKelas($unit, $kelas, $kelompok),
                ];
            })->values(),
        ]);
    }

    public function export(Request $request, SpreadsheetFile $sheets): BinaryFileResponse|RedirectResponse
    {
        try {
            $records = $this->filteredRaport($request)
                ->orderByDesc('r.urut')
                ->get();
        } catch (QueryException) {
            return $this->databaseUnavailable();
        }

        $rows = [['NIS', 'Nama', 'File raport', 'Jenjang', 'Kelas', 'Kelompok', 'Tahun akademik', 'Ujian', 'Tanggal upload']];

        foreach ($records as $record) {
            $rows[] = [
                trim((string) $record->NOCUST),
                trim((string) $record->NMCUST),
                trim((string) $record->FILE_RAPOT),
                trim((string) $record->jenjang),
                trim((string) $record->kelas),
                trim((string) $record->kelompok),
                trim((string) $record->THN_AKA),
                $record->UJIAN === 'AKHIR' ? 'Akhir' : 'Mid',
                $this->tanggalUpload($record->tgl_upload ?? null),
            ];
        }

        return $sheets->download('raport-akademik.xlsx', $rows, 'Raport');
    }

    public function file(int $urut): RedirectResponse
    {
        $record = RaportAkademik::query()->findOrFail($urut);
        $url = $this->files->url(RaportFileStorage::AKADEMIK, $record->FILE_RAPOT);
        abort_if($url === null, 404);

        return redirect()->away($url);
    }

    private function filteredRaport(Request $request): Builder
    {
        $selectedYear = trim((string) $request->query('thn_aka', ''));
        $selectedClass = (string) $request->query('kelas', '');
        $search = mb_substr(trim((string) $request->query('q', '')), 0, 70);

        $query = DB::table('akademik_rapot as r')
            ->leftJoin('scctcust as c', 'c.NOCUST', '=', 'r.NOCUST')
            ->select(
                'r.urut',
                'r.NOCUST',
                'r.THN_AKA',
                'r.FILE_RAPOT',
                'r.UJIAN',
                'r.tgl_upload',
                'c.NMCUST',
                'c.DESC02 as kelas',
                'c.DESC03 as kelompok',
                'c.CODE02 as jenjang',
            );

        if ($selectedYear !== '') {
            $query->where('r.THN_AKA', $selectedYear);
        }

        $pickedClass = Santri::pecahKunciKelas($selectedClass);

        if ($pickedClass !== null) {
            $query->whereRaw('TRIM(c.CODE02) = ?', [$pickedClass['unit']]);
            $query->whereRaw('TRIM(c.DESC02) = ?', [$pickedClass['kelas']]);
            $query->whereRaw('TRIM(c.DESC03) = ?', [$pickedClass['kelompok']]);
        }

        if ($search !== '') {
            $like = '%'.addcslashes($search, '%_\\').'%';
            $query->where(function ($inner) use ($like): void {
                $inner->where('r.NOCUST', 'like', $like)
                    ->orWhere('c.NMCUST', 'like', $like);
            });
        }

        return $query;
    }

    /**
     * @param  class-string<RaportAkademik>  $model
     */
    private function simpanBaris(string $model, string $nis, string $year, string $exam, string $filename, string $tanggal): void
    {
        $existing = $model::query()
            ->where('NOCUST', $nis)
            ->where('THN_AKA', $year)
            ->where('FILE_RAPOT', $filename)
            ->where('UJIAN', $exam)
            ->latest('urut')
            ->first();

        if ($existing !== null) {
            $existing->update(['tgl_upload' => $tanggal]);

            return;
        }

        $model::query()->create([
            'NOCUST' => $nis,
            'THN_AKA' => $year,
            'FILE_RAPOT' => $filename,
            'UJIAN' => $exam,
            'tgl_upload' => $tanggal,
        ]);
    }

    private function tanggalDariPermintaan(Request $request): string
    {
        return $request->date('tgl_upload')?->toDateString() ?? now()->toDateString();
    }

    private function tanggalUpload(mixed $value): string
    {
        if (! filled($value)) {
            return '';
        }

        return Carbon::parse($value)->format('d/m/Y');
    }

    private function databaseUnavailable(): RedirectResponse
    {
        return redirect()
            ->route('akademik.home')
            ->withErrors([
                'database' => 'Tidak dapat terhubung ke database. Periksa koneksi, lalu coba lagi.',
            ]);
    }
}
