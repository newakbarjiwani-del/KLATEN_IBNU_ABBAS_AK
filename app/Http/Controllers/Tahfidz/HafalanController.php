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
use Symfony\Component\HttpFoundation\Response;

class HafalanController extends Controller
{
    public function __construct(private SpreadsheetFile $sheets) {}

    /**
     * @param  array{slug: string, label: string, summary: string, group: string}  $current
     */
    public function gabungan(Request $request, array $current): View|RedirectResponse
    {
        $periode = trim((string) $request->query('periode', ''));
        $dari = $this->date((string) $request->query('dari', ''));
        $sampai = $this->date((string) $request->query('sampai', ''));
        $search = mb_substr(trim((string) $request->query('q', '')), 0, 70);
        $groups = $this->kolomGrup();
        $groupKey = ColumnGroup::key($request, $groups);

        try {
            $periods = $this->periods('akademik_upload_tahfidz', 'periode', 'akademik_upload_tahfidz.periode');
            $records = ColumnGroup::order(
                $this->filterSiswa(
                    DB::query()->fromSub($this->unggahan($periode, $dari, $sampai, $groupKey === 'NMCUST'), 'gabungan'),
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
            'columns' => $this->kolomTampil(),
            'records' => $records,
            'periods' => $periods,
            'periodLabel' => 'Periode',
            'selectedPeriod' => $periode,
            'search' => $search,
            'mode' => '',
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
            'heading' => 'Upload Tahfidz',
            'kolom' => $this->kolomUnggah(),
            'periode' => old('periode', $periode),
            'preview' => $preview,
            'openedCount' => $openedCount,
        ]);
    }

    public function buka(Request $request, string $kind): RedirectResponse
    {
        abort_unless($kind === 'upload-tahfidz', 404);

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
        abort_unless($kind === 'upload-tahfidz', 404);

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
            $payload = array_map(function (array $row) use ($periode, $today): array {
                return [
                    'tgl' => $today,
                    'periode' => $periode,
                    'NOCUST' => mb_substr($row['nis'], 0, 15),
                    'juzzdihapal' => mb_substr($row['juzzdihapal'], 0, 100),
                    'juzzposisi' => mb_substr($row['juzzposisi'], 0, 20),
                    'juzztotal' => mb_substr($row['juzztotal'], 0, 20),
                    'ziadah' => mb_substr($row['ziadah'], 0, 20),
                    'murojaah' => mb_substr($row['murojaah'], 0, 50),
                    'tuhfah' => mb_substr($row['tuhfah'], 0, 50),
                    'jazari' => mb_substr($row['jazari'], 0, 50),
                    'statusujian' => mb_substr($row['statusujian'], 0, 30),
                    'prestasi' => mb_substr($row['prestasi'], 0, 50),
                    'jumlahpertemuan' => mb_substr($row['jumlahpertemuan'], 0, 20),
                    'totalkehadiran' => mb_substr($row['totalkehadiran'], 0, 20),
                ];
            }, $ready);

            DB::transaction(function () use ($payload): void {
                foreach (array_chunk($payload, 200) as $chunk) {
                    DB::table('akademik_upload_tahfidz')->insert($chunk);
                }
            });
        } catch (QueryException|RuntimeException) {
            return back()->withErrors(['berkas' => 'Data tidak tersimpan. Periksa berkas, lalu coba lagi.'])->withInput();
        }

        $this->forgetImport($kind);
        Cache::forget('akademik_upload_tahfidz.periode');

        return redirect()
            ->route('tahfidz.menu', $kind)
            ->with('status', count($payload).' baris disimpan untuk periode '.$periode.'.');
    }

    public function template(string $kind): BinaryFileResponse
    {
        abort_unless($kind === 'upload-tahfidz', 404);

        return $this->sheets->download('template-tahfidz.xlsx', [
            ['NIS', 'Juz di hapal', 'Juz posisi', 'Juz total', 'Ziadah', 'Murojaah', 'Tuhfah', 'Jazari', 'Status ujian', 'Prestasi', 'Jumlah pertemuan', 'Total kehadiran'],
        ], 'Tahfidz');
    }

    public function eksporSetoran(Request $request): BinaryFileResponse|RedirectResponse
    {
        $periode = trim((string) $request->query('periode', ''));
        $dari = $this->date((string) $request->query('dari', ''));
        $sampai = $this->date((string) $request->query('sampai', ''));
        $search = mb_substr(trim((string) $request->query('q', '')), 0, 70);

        try {
            $records = $this->filterSiswa(
                DB::query()->fromSub($this->unggahan($periode, $dari, $sampai), 'gabungan'),
                $search,
            )
                ->orderByDesc('tgl')
                ->orderByDesc('urut')
                ->get();
            $this->fillNames($records);
        } catch (QueryException) {
            return $this->databaseUnavailable();
        }

        $rows = [array_column($this->kolomTampil(), 'label')];

        foreach ($records as $record) {
            $rows[] = array_map(
                fn (array $kolom): string => $kolom['key'] === 'tgl' && filled($record->tgl)
                    ? substr((string) $record->tgl, 0, 10)
                    : trim((string) ($record->{$kolom['key']} ?? '')),
                $this->kolomTampil(),
            );
        }

        return $this->sheets->download('data-tahfidz.xlsx', $rows, 'Tahfidz');
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

    /**
     * @param  array{slug: string, label: string, summary: string, group: string}  $current
     */
    public function laporan(Request $request, array $current): View|RedirectResponse
    {
        $bulan = $this->bulanDipilih($request);
        $search = mb_substr(trim((string) $request->query('q', '')), 0, 70);

        try {
            $periods = $this->periods('akademik_upload_tahfidz', 'periode', 'akademik_upload_tahfidz.periode');
            $records = $bulan === []
                ? null
                : $this->capaian($bulan, $search)->paginate(30)->withQueryString();
        } catch (QueryException) {
            return $this->databaseUnavailable();
        }

        if ($records !== null) {
            $this->fillNames($records->getCollection());
        }

        return view('tahfidz.laporan-capaian', [
            'account' => auth()->user(),
            'groups' => TahfidzMenu::groups(),
            'current' => $current,
            'periods' => $periods,
            'bulan' => $bulan,
            'search' => $search,
            'records' => $records,
            'kolom' => $this->kolomNilai(),
            'namaPeriode' => $periods->mapWithKeys(fn (string $nilai): array => [$nilai => $this->labelBulan($nilai)]),
        ]);
    }

    public function cetakLaporan(Request $request): Response|RedirectResponse
    {
        $bulan = $this->bulanDipilih($request);

        if ($bulan === []) {
            return redirect()
                ->route('tahfidz.menu', 'laporan-capaian')
                ->withErrors(['bulan' => 'Pilih minimal satu bulan.']);
        }

        try {
            $records = $this->capaian($bulan, mb_substr(trim((string) $request->query('q', '')), 0, 70))
                ->limit(5000)
                ->get();
            $this->fillNames($records);
        } catch (QueryException) {
            return $this->databaseUnavailable();
        }

        return response(view('tahfidz.laporan-capaian-cetak', [
            'records' => $records,
            'kolom' => $this->kolomNilai(),
            'labelBulan' => array_map($this->labelBulan(...), $bulan),
        ])->render(), 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }

    private function unggahan(string $periode, string $dari, string $sampai, bool $withName = false): Builder
    {
        $query = $this->barisTahfidz($withName);
        $this->constrain($query, $periode, $dari, $sampai);

        return $query;
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

    private function barisTahfidz(bool $withName): Builder
    {
        $query = DB::table('akademik_upload_tahfidz as h');

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
            'h.juzztotal',
            'h.ziadah',
            'h.murojaah',
            'h.tuhfah',
            'h.jazari',
            'h.statusujian',
            'h.prestasi',
            'h.jumlahpertemuan',
            'h.totalkehadiran',
            'h.tgl',
        ]);
    }

    /**
     * @param  array<int, string>  $bulan
     */
    private function capaian(array $bulan, string $search): Builder
    {
        return $this->filterSiswa(
            DB::query()
                ->fromSub($this->barisTahfidz(false)->whereIn('h.periode', $bulan), 'gabungan'),
            $search,
        )
            ->orderBy('periode')
            ->orderBy('NOCUST');
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

    /**
     * @return array<string, array{sql: string, label: string}>
     */
    private function kolomGrup(): array
    {
        $groups = [];

        foreach ($this->kolomTampil() as $kolom) {
            $groups[$kolom['key']] = ['sql' => $kolom['key'], 'label' => $kolom['label']];
        }

        return $groups;
    }

    /**
     * @return array<int, array{key: string, label: string}>
     */
    private function kolomTampil(): array
    {
        return [
            ['key' => 'periode', 'label' => 'Periode'],
            ['key' => 'NOCUST', 'label' => 'NIS'],
            ['key' => 'NMCUST', 'label' => 'Nama'],
            ...$this->kolomNilai(),
            ['key' => 'tgl', 'label' => 'Tanggal'],
        ];
    }

    /**
     * @return array<int, array{key: string, label: string}>
     */
    private function kolomUnggah(): array
    {
        return $this->kolomNilai();
    }

    /**
     * @return array<int, array{key: string, label: string}>
     */
    private function kolomNilai(): array
    {
        return [
            ['key' => 'juzzdihapal', 'label' => 'Juz di hapal'],
            ['key' => 'juzzposisi', 'label' => 'Juz posisi'],
            ['key' => 'juzztotal', 'label' => 'Juz total'],
            ['key' => 'ziadah', 'label' => 'Ziadah'],
            ['key' => 'murojaah', 'label' => 'Murojaah'],
            ['key' => 'tuhfah', 'label' => 'Tuhfah'],
            ['key' => 'jazari', 'label' => 'Jazari'],
            ['key' => 'statusujian', 'label' => 'Status ujian'],
            ['key' => 'prestasi', 'label' => 'Prestasi'],
            ['key' => 'jumlahpertemuan', 'label' => 'Jumlah pertemuan'],
            ['key' => 'totalkehadiran', 'label' => 'Total kehadiran'],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function bulanDipilih(Request $request): array
    {
        $raw = $request->query('bulan', []);

        if (is_string($raw)) {
            $raw = [$raw];
        }

        if (! is_array($raw)) {
            return [];
        }

        return collect($raw)
            ->map(fn (mixed $nilai): string => trim((string) $nilai))
            ->filter(fn (string $nilai): bool => $nilai !== '' && mb_strlen($nilai) <= 10)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function labelBulan(string $periode): string
    {
        if (preg_match('/^(\d{4})-?(\d{2})$/', $periode, $cocok) !== 1) {
            return $periode;
        }

        $nama = [
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
        $bulan = (int) $cocok[2];

        return isset($nama[$bulan]) ? $nama[$bulan].' '.$cocok[1] : $periode;
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
