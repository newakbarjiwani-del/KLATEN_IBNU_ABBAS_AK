<?php

namespace App\Http\Controllers\Kesantrian;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kesantrian\StoreRaportTahfidzRequest;
use App\Kesantrian\KesantrianMenu;
use App\Models\RaportTahfidz;
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

class RaportTahfidzController extends Controller
{
    public function __construct(private RaportFileStorage $files) {}

    public function upload(): View|RedirectResponse
    {
        try {
            $years = TahunAkademik::query()->orderByDesc('urut')->get();
        } catch (QueryException) {
            return $this->databaseUnavailable();
        }

        return view('kesantrian.upload-raport', [
            'account' => auth()->user(),
            'groups' => KesantrianMenu::groups(),
            'current' => KesantrianMenu::find('upload-raport'),
            'years' => $years,
        ]);
    }

    public function data(Request $request): View|RedirectResponse
    {
        try {
            $years = TahunAkademik::query()->orderByDesc('urut')->get();
            $classes = Santri::daftarKelas();
            $groups = Santri::daftarKelompok();

            $selectedYear = trim((string) $request->query('thn_aka', ''));
            $selectedClass = trim((string) $request->query('kelas', ''));
            $selectedGroup = trim((string) $request->query('kelompok', ''));
            $search = trim((string) $request->query('q', ''));
            $columnGroups = [
                'NOCUST' => ['sql' => 'r.NOCUST', 'label' => 'NIS'],
                'NMCUST' => ['sql' => 'c.NMCUST', 'label' => 'Nama siswa'],
                'kelas' => ['sql' => 'c.DESC02', 'label' => 'Kelas'],
                'kelompok' => ['sql' => 'c.DESC03', 'label' => 'Kelompok'],
                'jenjang' => ['sql' => 'c.CODE02', 'label' => 'Jenjang'],
                'THN_AKA' => ['sql' => 'r.THN_AKA', 'label' => 'Tahun akademik'],
                'UJIAN' => ['sql' => 'r.UJIAN', 'label' => 'Ujian'],
            ];
            $groupKey = ColumnGroup::key($request, $columnGroups);
            $records = ColumnGroup::order($this->filteredRaport($request), $groupKey, $columnGroups)
                ->orderByDesc('r.urut')
                ->paginate(30)
                ->withQueryString();

            $records->getCollection()->transform(function (object $record): object {
                $record->tautan = $this->files->url(RaportFileStorage::TAHFIDZ, $record->FILE_RAPOT);
                $record->tersedia = $record->tautan !== null;
                $record->tanggal_upload = $this->tanggalUpload($record->tgl_upload ?? null);

                return $record;
            });
        } catch (QueryException) {
            return $this->databaseUnavailable();
        }

        return view('kesantrian.data-raport', [
            'account' => auth()->user(),
            'groups' => KesantrianMenu::groups(),
            'current' => KesantrianMenu::find('data-raport'),
            'years' => $years,
            'classes' => $classes,
            'kelompok' => $groups,
            'selectedYear' => $selectedYear,
            'selectedClass' => $selectedClass,
            'selectedGroup' => $selectedGroup,
            'search' => $search,
            'records' => $records,
            'groupKey' => $groupKey,
            'groupLabel' => ColumnGroup::label($groupKey, $columnGroups),
        ]);
    }

    public function store(StoreRaportTahfidzRequest $request): RedirectResponse
    {
        $filename = $this->files->filename($request->file('berkas')->getClientOriginalName());

        try {
            $nis = $request->string('nocust')->toString();
            $year = $request->string('thn_aka')->toString();
            $exam = $request->string('ujian')->toString();
            $filename = $this->files->store(
                RaportFileStorage::TAHFIDZ,
                $request->file('berkas'),
                $filename,
                $nis,
                $year,
                $exam,
            );
            $this->simpanBaris(RaportTahfidz::class, $nis, $year, $exam, $filename, $this->tanggalDariPermintaan($request));
        } catch (RaportStorageException $exception) {
            return back()
                ->withErrors(['berkas' => $exception->getMessage()])
                ->withInput();
        } catch (QueryException) {
            return $this->databaseUnavailable();
        }

        return redirect()
            ->route('kesantrian.menu', 'upload-raport')
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
                ->get(['NOCUST', 'NMCUST']);
        } catch (QueryException) {
            return response()->json(['message' => 'Tidak dapat terhubung ke database. Periksa koneksi, lalu coba lagi.'], 503);
        }

        return response()->json([
            'items' => $items->map(fn (Santri $santri): array => [
                'nis' => trim((string) $santri->NOCUST),
                'nama' => $santri->nama(),
            ])->values(),
        ]);
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

        return response()->json([
            'nama' => $santri->nama(),
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

        $rows = [['NIS', 'Nama', 'Kelas', 'Kelompok', 'Jenjang', 'Tahun akademik', 'Ujian', 'File raport', 'Tanggal upload']];

        foreach ($records as $record) {
            $rows[] = [
                trim((string) $record->NOCUST),
                trim((string) $record->NMCUST),
                trim((string) $record->kelas),
                trim((string) $record->kelompok),
                trim((string) $record->jenjang),
                trim((string) $record->THN_AKA),
                $record->UJIAN === 'AKHIR' ? 'Akhir' : 'Mid',
                trim((string) $record->FILE_RAPOT),
                $this->tanggalUpload($record->tgl_upload ?? null),
            ];
        }

        return $sheets->download('raport-tahfidz.xlsx', $rows, 'Raport');
    }

    public function file(int $urut): RedirectResponse
    {
        $record = RaportTahfidz::query()->findOrFail($urut);
        $url = $this->files->url(RaportFileStorage::TAHFIDZ, $record->FILE_RAPOT);
        abort_if($url === null, 404);

        return redirect()->away($url);
    }

    private function filteredRaport(Request $request): Builder
    {
        $selectedYear = trim((string) $request->query('thn_aka', ''));
        $selectedClass = trim((string) $request->query('kelas', ''));
        $selectedGroup = trim((string) $request->query('kelompok', ''));
        $search = trim((string) $request->query('q', ''));

        $query = DB::table('akademik_rapot_tahfidzh as r')
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

        if ($selectedClass !== '') {
            $query->where('c.DESC02', $selectedClass);
        }

        if ($selectedGroup !== '') {
            $query->whereRaw('TRIM(c.DESC03) = ?', [$selectedGroup]);
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
     * @param  class-string<RaportTahfidz>  $model
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
            ->route('kesantrian.home')
            ->withErrors([
                'database' => 'Tidak dapat terhubung ke database. Periksa koneksi, lalu coba lagi.',
            ]);
    }
}
