<?php

namespace App\Http\Controllers\Kesantrian;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kesantrian\StoreReferensiPelanggaranRequest;
use App\Http\Requests\Kesantrian\UpdateReferensiPelanggaranRequest;
use App\Kesantrian\KesantrianMenu;
use App\Models\ReferensiPelanggaran;
use App\Services\SpreadsheetFile;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReferensiPelanggaranController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $request = request();
        $lockedUnit = $this->accountUnit();
        $selectedUnit = $lockedUnit ?? (string) $request->query('unit', '');
        $choices = [...ReferensiPelanggaran::UNITS, 'belum'];

        try {
            $unassigned = ReferensiPelanggaran::query()
                ->where(function ($query): void {
                    $query->whereNull('unit')->orWhere('unit', '');
                })
                ->count();

            if (! in_array($selectedUnit, $choices, true)) {
                $selectedUnit = $unassigned > 0 ? 'belum' : ReferensiPelanggaran::UNITS[0];
            }

            $items = ReferensiPelanggaran::query()
                ->when($selectedUnit === 'belum', function ($query): void {
                    $query->where(function ($inner): void {
                        $inner->whereNull('unit')->orWhere('unit', '');
                    });
                }, function ($query) use ($selectedUnit): void {
                    $query->where('unit', $selectedUnit);
                })
                ->orderBy('urut')
                ->get();
        } catch (QueryException) {
            return $this->databaseUnavailable();
        }

        return view('kesantrian.master-pelanggaran', [
            'account' => auth()->user(),
            'groups' => KesantrianMenu::groups(),
            'current' => KesantrianMenu::find('master-pelanggaran'),
            'items' => $items,
            'units' => ReferensiPelanggaran::UNITS,
            'selectedUnit' => $selectedUnit,
            'lockedUnit' => $lockedUnit,
            'unassigned' => $unassigned,
        ]);
    }

    public function store(StoreReferensiPelanggaranRequest $request): RedirectResponse
    {
        $unit = $this->accountUnit() ?? $request->validated('unit');

        try {
            ReferensiPelanggaran::query()->create([
                'jenis_pelangaran' => $request->validated('nama'),
                'skor' => $request->validated('skor'),
                'keterangan' => $request->note(),
                'unit' => $unit,
            ]);
        } catch (QueryException) {
            return $this->databaseUnavailable();
        }

        return redirect()
            ->route('kesantrian.menu', ['menu' => 'master-pelanggaran', 'unit' => $unit])
            ->with('status', 'Pelanggaran ditambahkan.');
    }

    public function update(UpdateReferensiPelanggaranRequest $request, int $urut): RedirectResponse
    {
        $item = ReferensiPelanggaran::query()->findOrFail($urut);
        $lockedUnit = $this->accountUnit();

        if ($lockedUnit !== null && $item->unit !== $lockedUnit) {
            abort(404);
        }

        $unit = $lockedUnit ?? $request->validated('unit');

        try {
            $item->update([
                'jenis_pelangaran' => $request->validated('nama'),
                'skor' => $request->validated('skor'),
                'keterangan' => $request->note(),
                'unit' => $unit,
            ]);
        } catch (QueryException) {
            return $this->databaseUnavailable();
        }

        return redirect()
            ->route('kesantrian.menu', ['menu' => 'master-pelanggaran', 'unit' => $unit])
            ->with('status', 'Pelanggaran diperbarui.');
    }

    public function destroy(int $urut): RedirectResponse
    {
        $item = ReferensiPelanggaran::query()->findOrFail($urut);
        $lockedUnit = $this->accountUnit();

        if ($lockedUnit !== null && $item->unit !== $lockedUnit) {
            abort(404);
        }

        try {
            $item->delete();
        } catch (QueryException) {
            return $this->databaseUnavailable();
        }

        $unit = $item->unit ?: 'belum';

        return redirect()
            ->route('kesantrian.menu', ['menu' => 'master-pelanggaran', 'unit' => $unit])
            ->with('status', 'Pelanggaran dihapus.');
    }

    public function template(SpreadsheetFile $sheets): BinaryFileResponse
    {
        return $sheets->download('template-master-pelanggaran.xlsx', [
            ['Unit', 'Nama pelanggaran', 'Skor', 'Keterangan'],
        ], 'Master Pelanggaran');
    }

    public function import(Request $request, SpreadsheetFile $sheets): RedirectResponse
    {
        $request->validate([
            'berkas' => ['required', 'file', 'mimes:xlsx,csv,txt', 'max:10240'],
        ], [
            'berkas.required' => 'Berkas Excel wajib dipilih.',
            'berkas.mimes' => 'Berkas harus Excel, CSV, atau TXT.',
            'berkas.max' => 'Ukuran berkas paling besar 10 MB.',
        ]);

        $file = $request->file('berkas');
        $rows = $sheets->read($file->getRealPath(), (string) $file->getClientOriginalExtension());
        $lockedUnit = $this->accountUnit();
        $added = 0;
        $updated = 0;
        $skipped = 0;
        $dataRows = 0;
        $targetUnit = $lockedUnit;

        if ($rows !== []) {
            $header = array_map(fn (string $cell): string => $this->headerName($cell), $rows[0]);
            $columns = $this->columns($header);
            $start = $columns === [] ? 0 : 1;
            $columns = $columns === [] ? ['unit' => 0, 'nama' => 1, 'skor' => 2, 'keterangan' => 3] : $columns;

            foreach (array_slice($rows, $start) as $row) {
                if ($dataRows >= 1000) {
                    break;
                }

                $nama = trim((string) ($row[$columns['nama'] ?? 1] ?? ''));
                $unit = ReferensiPelanggaran::canonicalUnit((string) ($row[$columns['unit'] ?? 0] ?? ''));
                $skor = trim((string) ($row[$columns['skor'] ?? 2] ?? ''));
                $note = trim((string) ($row[$columns['keterangan'] ?? 3] ?? ''));

                if ($nama === '' && $unit === null && $skor === '') {
                    continue;
                }

                $dataRows++;

                if ($nama === '' || $unit === null || ! preg_match('/^\d+$/', $skor) || (int) $skor > 32767 || mb_strlen($note) > 20) {
                    $skipped++;

                    continue;
                }

                if ($lockedUnit !== null && $unit !== $lockedUnit) {
                    $skipped++;

                    continue;
                }

                $targetUnit ??= $unit;

                try {
                    $existing = ReferensiPelanggaran::query()
                        ->where('unit', $unit)
                        ->where('jenis_pelangaran', $nama)
                        ->first();

                    if ($existing !== null) {
                        $existing->update([
                            'skor' => (int) $skor,
                            'keterangan' => $note !== '' ? $note : null,
                        ]);
                        $updated++;

                        continue;
                    }

                    ReferensiPelanggaran::query()->create([
                        'jenis_pelangaran' => $nama,
                        'skor' => (int) $skor,
                        'keterangan' => $note !== '' ? $note : null,
                        'unit' => $unit,
                    ]);
                    $added++;
                } catch (QueryException) {
                    return $this->databaseUnavailable();
                }
            }
        }

        $unit = $targetUnit ?? ReferensiPelanggaran::UNITS[0];

        return redirect()
            ->route('kesantrian.menu', ['menu' => 'master-pelanggaran', 'unit' => $unit])
            ->with('status', "Unggah selesai. {$added} ditambahkan, {$updated} diperbarui, {$skipped} dilewati.");
    }

    private function accountUnit(): ?string
    {
        $unit = ReferensiPelanggaran::canonicalUnit((string) auth()->user()?->unit);

        return $unit;
    }

    /**
     * @param  array<int, string>  $header
     * @return array<string, int>
     */
    private function columns(array $header): array
    {
        $aliases = [
            'unit' => ['unit', 'sekolah'],
            'nama' => ['nama', 'namapelanggaran', 'jenis', 'jenispelanggaran'],
            'skor' => ['skor'],
            'keterangan' => ['keterangan'],
        ];
        $columns = [];

        foreach ($header as $index => $name) {
            foreach ($aliases as $field => $names) {
                if (in_array($name, $names, true)) {
                    $columns[$field] = $index;
                }
            }
        }

        if (! isset($columns['nama'], $columns['skor'])) {
            return [];
        }

        return $columns;
    }

    private function headerName(string $value): string
    {
        $value = strtolower(trim($value));

        return preg_replace('/[^a-z]/', '', $value) ?? $value;
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
