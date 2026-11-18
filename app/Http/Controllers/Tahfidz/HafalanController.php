<?php

namespace App\Http\Controllers\Tahfidz;

use App\Http\Controllers\Controller;
use App\Models\Santri;
use App\Services\SpreadsheetFile;
use App\Tables\ColumnGroup;
use App\Tahfidz\HafalanSheet;
use App\Tahfidz\TahfidzMenu;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class HafalanController extends Controller
{
    public function __construct(private SpreadsheetFile $sheets) {}

    /**
     * @param  array{slug: string, label: string, summary: string, group: string}  $current
     */
    public function gabungan(Request $request, array $current): View|RedirectResponse
    {
        $mode = $this->mode((string) $request->query('mode', ''));
        $periode = trim((string) $request->query('periode', ''));
        $dari = $this->date((string) $request->query('dari', ''));
        $sampai = $this->date((string) $request->query('sampai', ''));
        $search = mb_substr(trim((string) $request->query('q', '')), 0, 70);

        $groups = [
            'periode' => ['sql' => 'periode', 'label' => 'Periode'],
            'NOCUST' => ['sql' => 'NOCUST', 'label' => 'NIS'],
            'NMCUST' => ['sql' => 'NMCUST', 'label' => 'Nama'],
            'juzzdihapal' => ['sql' => 'juzzdihapal', 'label' => 'Juzz Di Hapal'],
            'juzzposisi' => ['sql' => 'juzzposisi', 'label' => 'Juzz Posisi'],
            'total_juzz' => ['sql' => 'total_juzz', 'label' => 'Total Juzz'],
            'ziadah' => ['sql' => 'ziadah', 'label' => 'Ziadah'],
            'juzz' => ['sql' => 'juzz', 'label' => 'Juzz'],
            'tgl' => ['sql' => 'tgl', 'label' => 'Tanggal'],
        ];
        $groupKey = ColumnGroup::key($request, $groups);

        try {
            $periods = $this->mergedPeriods($mode);
            $records = ColumnGroup::order(
                $this->filterSiswa(
                    DB::query()->fromSub($this->combined($mode, $periode, $dari, $sampai, $groupKey === 'NMCUST'), 'gabungan'),
                    $search,
                ),
                $groupKey,
                $groups,
            )
                ->orderByDesc('tgl')
                ->orderByDesc('urut')
                ->paginate(30)
                ->withQueryString();

            if ($groupKey !== 'NMCUST') {
                $this->fillNames($records->getCollection());
            }
        } catch (QueryException) {
            return $this->databaseUnavailable();
        }

        return view('tahfidz.hafalan', [
            'account' => auth()->user(),
            'groups' => TahfidzMenu::groups(),
            'current' => $current,
            'screen' => 'gabungan',
            'columns' => [
                ['key' => 'periode', 'label' => 'Periode'],
                ['key' => 'NOCUST', 'label' => 'NIS'],
                ['key' => 'NMCUST', 'label' => 'Nama'],
                ['key' => 'juzzdihapal', 'label' => 'Juzz Di Hapal'],
                ['key' => 'juzzposisi', 'label' => 'Juzz Posisi'],
                ['key' => 'total_juzz', 'label' => 'Total Juzz'],
                ['key' => 'ziadah', 'label' => 'Ziadah'],
                ['key' => 'juzz', 'label' => 'Juzz'],
                ['key' => 'tgl', 'label' => 'Tanggal'],
            ],
            'records' => $records,
            'periods' => $periods,
            'periodLabel' => 'Periode',
            'selectedPeriod' => $periode,
            'search' => $search,
            'mode' => $mode,
            'dari' => $dari,
            'sampai' => $sampai,
            'groupKey' => $groupKey,
            'groupLabel' => ColumnGroup::label($groupKey, $groups),
        ]);
    }

    /**
     * @param  array{slug: string, label: string, summary: string, group: string}  $current
     */
    public function impor(Request $request, array $current, string $kind): View
    {
        $opened = session('tahfidz_import');
        $preview = [];
        $openedCount = 0;
        $periode = '';

        if (is_array($opened) && ($opened['kind'] ?? '') === $kind) {
            $preview = is_array($opened['preview'] ?? null) ? $opened['preview'] : [];
            $openedCount = (int) ($opened['count'] ?? 0);
            $periode = (string) ($opened['periode'] ?? '');
        }

        return view('tahfidz.import', [
            'account' => auth()->user(),
            'groups' => TahfidzMenu::groups(),
            'current' => $current,
            'kind' => $kind,
            'heading' => $kind === 'murajaah' ? 'Import Murajaah Santri' : 'Import Setoran Santri',
            'periode' => old('periode', $periode),
            'preview' => $preview,
            'openedCount' => $openedCount,
        ]);
    }

    public function buka(Request $request, string $kind): RedirectResponse
    {
        abort_unless(in_array($kind, ['murajaah', 'setoran'], true), 404);

        $validated = $request->validate([
            'periode' => ['required', 'string', 'max:10'],
            'berkas' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:10240'],
        ], [
            'periode.required' => 'Periode wajib diisi.',
            'berkas.required' => 'Berkas wajib dipilih.',
            'berkas.mimes' => 'Berkas harus CSV atau Excel.',
        ]);

        $upload = $request->file('berkas');
        $extension = strtolower((string) $upload?->getClientOriginalExtension());
        $path = 'tahfidz-import/'.$kind.'-'.auth()->id().'.'.$extension;
        $this->forgetImport($kind);

        try {
            Storage::disk('local')->put($path, $upload?->getContent() ?? '');
            $rows = (new HafalanSheet)->read(Storage::disk('local')->path($path), $kind);
        } catch (RuntimeException) {
            Storage::disk('local')->delete($path);

            return back()->withErrors(['berkas' => 'Berkas tidak dapat dibaca.'])->withInput();
        }

        if ($rows === []) {
            Storage::disk('local')->delete($path);

            return back()->withErrors(['berkas' => 'Berkas tidak berisi data.'])->withInput();
        }

        if (count($rows) > 5000) {
            Storage::disk('local')->delete($path);

            return back()->withErrors(['berkas' => 'Berkas paling banyak 5000 baris.'])->withInput();
        }

        $preview = $this->decorate($rows);
        session([
            'tahfidz_import' => [
                'kind' => $kind,
                'path' => $path,
                'periode' => trim($validated['periode']),
                'count' => count($preview),
                'preview' => array_slice($preview, 0, 80),
            ],
        ]);

        return redirect()
            ->route('tahfidz.menu', $kind)
            ->with('status', count($preview).' baris siap diperiksa.');
    }

    public function simpan(Request $request, string $kind): RedirectResponse
    {
        abort_unless(in_array($kind, ['murajaah', 'setoran'], true), 404);

        $validated = $request->validate([
            'periode' => ['required', 'string', 'max:10'],
        ], [
            'periode.required' => 'Periode wajib diisi.',
        ]);

        $opened = session('tahfidz_import');

        if (! is_array($opened) || ($opened['kind'] ?? '') !== $kind || ! is_string($opened['path'] ?? null)) {
            return back()->withErrors(['berkas' => 'Buka berkas terlebih dahulu.'])->withInput();
        }

        $path = $opened['path'];

        if (! Storage::disk('local')->exists($path)) {
            return back()->withErrors(['berkas' => 'Buka berkas terlebih dahulu.'])->withInput();
        }

        try {
            $rows = $this->decorate((new HafalanSheet)->read(Storage::disk('local')->path($path), $kind));
            $ready = array_values(array_filter($rows, fn (array $row): bool => $row['siap'] === true));

            if ($ready === []) {
                return back()->withErrors(['berkas' => 'Tidak ada NIS yang dikenali.'])->withInput();
            }

            $periode = trim($validated['periode']);
            $today = now()->toDateString();
            $payload = array_map(function (array $row) use ($kind, $periode, $today): array {
                if ($kind === 'murajaah') {
                    return [
                        'tgl' => $today,
                        'juzz' => mb_substr($row['juzz'], 0, 50),
                        'totjuzz' => mb_substr($row['totjuzz'], 0, 50),
                        'NOCUST' => mb_substr($row['nis'], 0, 10),
                        'periode' => $periode,
                        'StatusLulus' => mb_substr($row['lulus'], 0, 15),
                        'StatusPrestasi' => mb_substr($row['prestasi'], 0, 15),
                    ];
                }

                return [
                    'tgl' => $today,
                    'juzzdihapal' => mb_substr($row['juzzdihapal'], 0, 100),
                    'juzzposisi' => mb_substr($row['juzzposisi'], 0, 20),
                    'juzztotal' => mb_substr($row['juzztotal'], 0, 20),
                    'NOCUST' => mb_substr($row['nis'], 0, 10),
                    'ziadah' => mb_substr($row['ziadah'], 0, 20),
                    'periode' => $periode,
                    'StatusLulus' => mb_substr($row['lulus'], 0, 15),
                    'StatusPrestasi' => mb_substr($row['prestasi'], 0, 15),
                ];
            }, $ready);

            DB::transaction(function () use ($kind, $payload): void {
                $table = $kind === 'murajaah' ? 'akademik_murajaah' : 'akademik_setoran';

                foreach (array_chunk($payload, 200) as $chunk) {
                    DB::table($table)->insert($chunk);
                }
            });
        } catch (QueryException|RuntimeException) {
            return back()->withErrors(['berkas' => 'Data tidak tersimpan. Periksa berkas, lalu coba lagi.'])->withInput();
        }

        $this->forgetImport($kind);
        Cache::forget($kind === 'murajaah' ? 'akademik_murajaah.periode' : 'akademik_setoran.periode');

        return redirect()
            ->route('tahfidz.menu', $kind)
            ->with('status', count($payload).' baris disimpan untuk periode '.$periode.'.');
    }

    public function template(string $kind): BinaryFileResponse
    {
        abort_unless(in_array($kind, ['murajaah', 'setoran'], true), 404);

        if ($kind === 'murajaah') {
            return $this->sheets->download('template-murajaah.xlsx', [
                ['NIS', 'Total Juzz', 'Juzz', 'Lulus', 'Prestasi'],
                ['', '2', '1-2', 'lulus', 'baik'],
            ], 'Murajaah');
        }

        return $this->sheets->download('template-setoran.xlsx', [
            ['NIS', 'Juzz Di Hapal', 'Juzz Posisi', 'Juzz Total', 'Ziadah', 'Lulus', 'Prestasi'],
            ['', '30', '5', '5', '0.5', 'lulus', 'baik'],
        ], 'Setoran');
    }

    public function eksporSetoran(Request $request): BinaryFileResponse|RedirectResponse
    {
        $mode = $this->mode((string) $request->query('mode', ''));
        $periode = trim((string) $request->query('periode', ''));
        $dari = $this->date((string) $request->query('dari', ''));
        $sampai = $this->date((string) $request->query('sampai', ''));
        $search = mb_substr(trim((string) $request->query('q', '')), 0, 70);

        try {
            $records = $this->filterSiswa(
                DB::query()
                    ->fromSub($this->combined($mode, $periode, $dari, $sampai), 'gabungan'),
                $search,
            )
                ->orderByDesc('tgl')
                ->orderByDesc('urut')
                ->get();
            $this->fillNames($records);
        } catch (QueryException) {
            return $this->databaseUnavailable();
        }

        $rows = [[
            'Periode', 'NIS', 'Nama', 'Juzz Di Hapal', 'Juzz Posisi', 'Total Juzz', 'Ziadah', 'Juzz', 'Tanggal',
        ]];

        foreach ($records as $record) {
            $rows[] = [
                trim((string) $record->periode),
                trim((string) $record->NOCUST),
                trim((string) $record->NMCUST),
                trim((string) $record->juzzdihapal),
                trim((string) $record->juzzposisi),
                trim((string) $record->total_juzz),
                trim((string) $record->ziadah),
                trim((string) $record->juzz),
                filled($record->tgl) ? substr((string) $record->tgl, 0, 10) : '',
            ];
        }

        return $this->sheets->download('setoran-bacaan.xlsx', $rows, 'Setoran');
    }

    public function eksporLama(Request $request): BinaryFileResponse|RedirectResponse
    {
        $search = mb_substr(trim((string) $request->query('q', '')), 0, 70);
        $selectedPeriod = trim((string) $request->query('periode', ''));

        try {
            $query = DB::table('oldakademik_setoran as h')
                ->leftJoin('scctcust as s', 's.NOCUST', '=', 'h.NOCUST')
                ->select([
                    'h.tgl', 'h.NOCUST', 's.NMCUST', 'h.surat', 'h.ayat', 'h.ayat_akhir',
                    'h.nilai', 'h.thn_aka', 'h.juzz', 'h.halaman', 'h.lembar', 'h.periode', 'h.lulus',
                ])
                ->orderByDesc('h.urut');

            if ($selectedPeriod !== '') {
                $query->where('h.thn_aka', $selectedPeriod);
            }

            if ($search !== '') {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $query->where(function ($inner) use ($like): void {
                    $inner->where('h.NOCUST', 'like', $like)
                        ->orWhere('s.NMCUST', 'like', $like);
                });
            }

            $records = $query->get();
        } catch (QueryException) {
            return $this->databaseUnavailable();
        }

        $rows = [[
            'Tanggal', 'NIS', 'Nama', 'Surat', 'Ayat', 'Ayat akhir', 'Nilai',
            'Tahun akademik', 'Juz', 'Halaman', 'Lembar', 'Periode', 'Lulus',
        ]];

        foreach ($records as $record) {
            $rows[] = [
                filled($record->tgl) ? substr((string) $record->tgl, 0, 10) : '',
                trim((string) $record->NOCUST),
                trim((string) $record->NMCUST),
                trim((string) $record->surat),
                trim((string) $record->ayat),
                trim((string) $record->ayat_akhir),
                trim((string) $record->nilai),
                trim((string) $record->thn_aka),
                trim((string) $record->juzz),
                trim((string) $record->halaman),
                trim((string) $record->lembar),
                trim((string) $record->periode),
                trim((string) $record->lulus),
            ];
        }

        return $this->sheets->download('setoran-lama.xlsx', $rows, 'Setoran lama');
    }

    /**
     * @param  array{slug: string, label: string, summary: string, group: string}  $current
     */
    public function lama(Request $request, array $current): View|RedirectResponse
    {
        try {
            $search = mb_substr(trim((string) $request->query('q', '')), 0, 70);
            $selectedPeriod = trim((string) $request->query('periode', ''));
            $periods = $this->periods('oldakademik_setoran', 'thn_aka', 'oldakademik_setoran.thn_aka');

            $query = DB::table('oldakademik_setoran as h')
                ->leftJoin('scctcust as s', 's.NOCUST', '=', 'h.NOCUST')
                ->select([
                    'h.urut',
                    'h.tgl',
                    'h.NOCUST',
                    'h.surat',
                    'h.ayat',
                    'h.ayat_akhir',
                    'h.nilai',
                    'h.thn_aka',
                    'h.juzz',
                    'h.halaman',
                    'h.lembar',
                    'h.periode',
                    'h.lulus',
                    's.NMCUST',
                ]);

            if ($selectedPeriod !== '') {
                $query->where('h.thn_aka', $selectedPeriod);
            }

            if ($search !== '') {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $query->where(function ($inner) use ($like): void {
                    $inner->where('h.NOCUST', 'like', $like)
                        ->orWhere('s.NMCUST', 'like', $like);
                });
            }

            $groups = [
                'tgl' => ['sql' => 'h.tgl', 'label' => 'Tanggal'],
                'NOCUST' => ['sql' => 'h.NOCUST', 'label' => 'NIS'],
                'NMCUST' => ['sql' => 's.NMCUST', 'label' => 'Nama'],
                'surat' => ['sql' => 'h.surat', 'label' => 'Surat'],
                'ayat' => ['sql' => 'h.ayat', 'label' => 'Ayat'],
                'ayat_akhir' => ['sql' => 'h.ayat_akhir', 'label' => 'Ayat akhir'],
                'nilai' => ['sql' => 'h.nilai', 'label' => 'Nilai'],
                'thn_aka' => ['sql' => 'h.thn_aka', 'label' => 'Tahun akademik'],
                'juzz' => ['sql' => 'h.juzz', 'label' => 'Juz'],
                'halaman' => ['sql' => 'h.halaman', 'label' => 'Halaman'],
                'lembar' => ['sql' => 'h.lembar', 'label' => 'Lembar'],
                'periode' => ['sql' => 'h.periode', 'label' => 'Periode'],
                'lulus' => ['sql' => 'h.lulus', 'label' => 'Lulus'],
            ];
            $groupKey = ColumnGroup::key($request, $groups);
            $records = ColumnGroup::order($query, $groupKey, $groups)
                ->orderByDesc('h.urut')
                ->paginate(25)
                ->withQueryString();
        } catch (QueryException) {
            return $this->databaseUnavailable();
        }

        return view('tahfidz.hafalan', [
            'account' => auth()->user(),
            'groups' => TahfidzMenu::groups(),
            'current' => $current,
            'screen' => 'lama',
            'columns' => [
                ['key' => 'tgl', 'label' => 'Tanggal'],
                ['key' => 'NOCUST', 'label' => 'NIS'],
                ['key' => 'NMCUST', 'label' => 'Nama'],
                ['key' => 'surat', 'label' => 'Surat'],
                ['key' => 'ayat', 'label' => 'Ayat'],
                ['key' => 'ayat_akhir', 'label' => 'Ayat akhir'],
                ['key' => 'nilai', 'label' => 'Nilai'],
                ['key' => 'thn_aka', 'label' => 'Tahun akademik'],
                ['key' => 'juzz', 'label' => 'Juz'],
                ['key' => 'halaman', 'label' => 'Halaman'],
                ['key' => 'lembar', 'label' => 'Lembar'],
                ['key' => 'periode', 'label' => 'Periode'],
                ['key' => 'lulus', 'label' => 'Lulus'],
            ],
            'records' => $records,
            'periods' => $periods,
            'periodLabel' => 'Tahun akademik',
            'selectedPeriod' => $selectedPeriod,
            'search' => $search,
            'mode' => '',
            'dari' => '',
            'sampai' => '',
            'groupKey' => $groupKey,
            'groupLabel' => ColumnGroup::label($groupKey, $groups),
        ]);
    }

    private function combined(string $mode, string $periode, string $dari, string $sampai, bool $withName = false): Builder
    {
        $setoran = $this->setoranRows($withName);
        $murajaah = $this->murajaahRows($withName);
        $this->constrain($setoran, $periode, $dari, $sampai);
        $this->constrain($murajaah, $periode, $dari, $sampai);

        if ($mode === 'setoran') {
            return $setoran;
        }

        if ($mode === 'murajaah') {
            return $murajaah;
        }

        return $setoran->unionAll($murajaah);
    }

    private function filterSiswa(Builder $query, string $search): Builder
    {
        if ($search === '') {
            return $query;
        }

        $like = '%'.addcslashes($search, '%_\\').'%';
        $ids = DB::table('scctcust')
            ->where('NMCUST', 'like', $like)
            ->limit(300)
            ->pluck('NOCUST')
            ->map(fn (mixed $id): string => trim((string) $id))
            ->filter(fn (string $id): bool => $id !== '')
            ->unique()
            ->values();

        return $query->where(function (Builder $inner) use ($like, $ids): void {
            $inner->where('NOCUST', 'like', $like);

            if ($ids->isNotEmpty()) {
                $inner->orWhereIn('NOCUST', $ids->all());
            }
        });
    }

    private function setoranRows(bool $withName): Builder
    {
        $query = DB::table('akademik_setoran as h');

        if ($withName) {
            $query->leftJoin('scctcust as s', 's.NOCUST', '=', 'h.NOCUST');
        }

        return $query->select([
            'h.urut',
            'h.periode',
            'h.NOCUST',
            $withName ? 's.NMCUST' : DB::raw("'' as NMCUST"),
            'h.juzzdihapal',
            'h.juzzposisi',
            'h.juzztotal as total_juzz',
            'h.ziadah',
            DB::raw("'' as juzz"),
            'h.tgl',
        ]);
    }

    private function murajaahRows(bool $withName): Builder
    {
        $query = DB::table('akademik_murajaah as h');

        if ($withName) {
            $query->leftJoin('scctcust as s', 's.NOCUST', '=', 'h.NOCUST');
        }

        return $query->select([
            'h.urut',
            'h.periode',
            'h.NOCUST',
            $withName ? 's.NMCUST' : DB::raw("'' as NMCUST"),
            DB::raw("'' as juzzdihapal"),
            DB::raw("'' as juzzposisi"),
            'h.totjuzz as total_juzz',
            DB::raw("'' as ziadah"),
            'h.juzz',
            'h.tgl',
        ]);
    }

    private function fillNames(Collection $rows): void
    {
        $ids = $rows
            ->map(fn (object $row): string => (string) $row->NOCUST)
            ->filter(fn (string $id): bool => $id !== '')
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return;
        }

        $names = collect();

        foreach ($ids->chunk(500) as $chunk) {
            $found = DB::table('scctcust')
                ->whereIn('NOCUST', $chunk->all())
                ->pluck('NMCUST', 'NOCUST');
            $names = $names->union($found);
        }

        $rows->each(function (object $row) use ($names): void {
            $row->NMCUST = trim((string) ($names[$row->NOCUST] ?? ''));
        });
    }

    private function constrain(Builder $query, string $periode, string $dari, string $sampai): void
    {
        if ($periode !== '') {
            $query->where('h.periode', $periode);
        }

        if ($dari !== '') {
            $query->whereDate('h.tgl', '>=', $dari);
        }

        if ($sampai !== '') {
            $query->whereDate('h.tgl', '<=', $sampai);
        }
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function decorate(array $rows): array
    {
        $students = Santri::query()
            ->whereIn('NOCUST', array_column($rows, 'nis'))
            ->get(['NOCUST', 'NMCUST'])
            ->keyBy(fn (Santri $santri): string => trim((string) $santri->NOCUST));

        return array_map(function (array $row) use ($students): array {
            $santri = $students->get($row['nis']);
            $row['nama'] = $santri instanceof Santri ? $santri->nama() : '';
            $row['siap'] = $santri instanceof Santri;

            return $row;
        }, $rows);
    }

    /**
     * @return Collection<int, string>
     */
    private function mergedPeriods(string $mode): Collection
    {
        if ($mode === 'murajaah') {
            return $this->periods('akademik_murajaah', 'periode', 'akademik_murajaah.periode');
        }

        if ($mode === 'setoran') {
            return $this->periods('akademik_setoran', 'periode', 'akademik_setoran.periode');
        }

        return $this->periods('akademik_setoran', 'periode', 'akademik_setoran.periode')
            ->merge($this->periods('akademik_murajaah', 'periode', 'akademik_murajaah.periode'))
            ->unique()
            ->sortDesc()
            ->values();
    }

    /**
     * @return Collection<int, string>
     */
    private function periods(string $table, string $column, string $cacheKey): Collection
    {
        $cached = Cache::get($cacheKey);

        if ($cached instanceof Collection) {
            return $cached;
        }

        if (is_array($cached)) {
            return collect($cached);
        }

        Cache::forget($cacheKey);

        /** @var array<int, string> $values */
        $values = DB::table($table)
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderByDesc($column)
            ->pluck($column)
            ->map(fn (mixed $value): string => trim((string) $value))
            ->filter(fn (string $value): bool => $value !== '')
            ->unique()
            ->values()
            ->all();

        Cache::put($cacheKey, $values, now()->addMinutes(10));

        return collect($values);
    }

    private function mode(string $mode): string
    {
        return in_array($mode, ['setoran', 'murajaah'], true) ? $mode : '';
    }

    private function date(string $value): string
    {
        $value = trim($value);

        if ($value === '' || $value === '0000-00-00') {
            return '';
        }

        $parsed = date_create($value);

        return $parsed === false ? '' : $parsed->format('Y-m-d');
    }

    private function forgetImport(string $kind): void
    {
        $opened = session('tahfidz_import');

        if (is_array($opened) && ($opened['kind'] ?? '') === $kind && is_string($opened['path'] ?? null)) {
            Storage::disk('local')->delete($opened['path']);
        }

        session()->forget('tahfidz_import');
    }

    private function databaseUnavailable(): RedirectResponse
    {
        return redirect()
            ->route('tahfidz.home')
            ->withErrors([
                'database' => 'Tidak dapat terhubung ke database. Periksa koneksi, lalu coba lagi.',
            ]);
    }
}
