<?php

namespace App\Http\Controllers\Kesantrian;

use App\Http\Controllers\Controller;
use App\Kesantrian\KesantrianMenu;
use App\Kesantrian\UnitRaport;
use App\Models\ItemRaportKesantrian;
use App\Models\KategoriRaportKesantrian;
use App\Models\ReferensiPelanggaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KategoriRaportController extends Controller
{
    public function index(): View
    {
        $unit = UnitRaport::dipilih(request()->query('unit'));

        return view('kesantrian.master-kategori-raport', [
            'account' => auth()->user(),
            'groups' => KesantrianMenu::groups(),
            'current' => KesantrianMenu::find('master-kategori-raport'),
            'kategori' => KategoriRaportKesantrian::untuk($unit),
            'units' => ReferensiPelanggaran::UNITS,
            'selectedUnit' => $unit,
            'lockedUnit' => UnitRaport::terkunci(),
        ]);
    }

    public function storeKategori(Request $request): RedirectResponse
    {
        $unit = $this->unit($request);
        $data = $this->validatedName($request);

        $exists = KategoriRaportKesantrian::query()
            ->where('unit', $unit)
            ->where('nama', $data['nama'])
            ->exists();

        if ($exists) {
            return $this->back($unit, 'Kategori dengan nama itu sudah ada di unit ini.');
        }

        KategoriRaportKesantrian::query()->create([
            'unit' => $unit,
            'nama' => $data['nama'],
            'urut' => $data['urut'] ?? $this->nextKategori($unit),
        ]);

        return $this->back($unit, 'Kategori ditambahkan.', true);
    }

    public function updateKategori(Request $request, int $id): RedirectResponse
    {
        $kategori = $this->kategori($id);
        $data = $this->validatedName($request);

        $exists = KategoriRaportKesantrian::query()
            ->where('unit', $kategori->unit)
            ->where('nama', $data['nama'])
            ->whereKeyNot($kategori->id)
            ->exists();

        if ($exists) {
            return $this->back($kategori->unit, 'Kategori dengan nama itu sudah ada di unit ini.');
        }

        $kategori->update([
            'nama' => $data['nama'],
            'urut' => $data['urut'] ?? $kategori->urut,
        ]);

        return $this->back($kategori->unit, 'Kategori diperbarui.', true);
    }

    public function destroyKategori(int $id): RedirectResponse
    {
        $kategori = $this->kategori($id);
        $kategori->items()->delete();
        $kategori->delete();

        return $this->back($kategori->unit, 'Kategori dihapus. Nilai lama tetap tersimpan.', true);
    }

    public function storeItem(Request $request): RedirectResponse
    {
        $kategori = $this->kategori((int) $request->input('kategori_id'));
        $data = $this->validatedName($request);

        $exists = $kategori->items()->where('nama', $data['nama'])->exists();
        if ($exists) {
            return $this->back($kategori->unit, 'Butir dengan nama itu sudah ada di kategori ini.');
        }

        $kategori->items()->create([
            'nama' => $data['nama'],
            'urut' => $data['urut'] ?? ((int) $kategori->items()->max('urut') + 1),
        ]);

        return $this->back($kategori->unit, 'Butir ditambahkan.', true);
    }

    public function updateItem(Request $request, int $id): RedirectResponse
    {
        $item = $this->item($id);
        $data = $this->validatedName($request);

        $exists = ItemRaportKesantrian::query()
            ->where('kategori_id', $item->kategori_id)
            ->where('nama', $data['nama'])
            ->whereKeyNot($item->id)
            ->exists();

        if ($exists) {
            return $this->back($item->kategori->unit, 'Butir dengan nama itu sudah ada di kategori ini.');
        }

        $item->update([
            'nama' => $data['nama'],
            'urut' => $data['urut'] ?? $item->urut,
        ]);

        return $this->back($item->kategori->unit, 'Butir diperbarui.', true);
    }

    public function destroyItem(int $id): RedirectResponse
    {
        $item = $this->item($id);
        $unit = $item->kategori->unit;
        $item->delete();

        return $this->back($unit, 'Butir dihapus. Nilai lama tetap tersimpan.', true);
    }

    private function kategori(int $id): KategoriRaportKesantrian
    {
        $kategori = KategoriRaportKesantrian::query()->findOrFail($id);
        $locked = UnitRaport::terkunci();
        abort_if($locked !== null && $kategori->unit !== $locked, 404);

        return $kategori;
    }

    private function item(int $id): ItemRaportKesantrian
    {
        $item = ItemRaportKesantrian::query()->with('kategori')->findOrFail($id);
        $locked = UnitRaport::terkunci();
        abort_if($item->kategori === null || ($locked !== null && $item->kategori->unit !== $locked), 404);

        return $item;
    }

    private function unit(Request $request): string
    {
        return UnitRaport::dipilih($request->input('unit'));
    }

    /**
     * @return array{nama: string, urut: int|null}
     */
    private function validatedName(Request $request): array
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:80'],
            'urut' => ['nullable', 'integer', 'min:1', 'max:999'],
        ]);

        return [
            'nama' => trim($data['nama']),
            'urut' => isset($data['urut']) ? (int) $data['urut'] : null,
        ];
    }

    private function nextKategori(string $unit): int
    {
        return (int) KategoriRaportKesantrian::query()->where('unit', $unit)->max('urut') + 1;
    }

    private function back(string $unit, string $message, bool $success = false): RedirectResponse
    {
        $redirect = redirect()->route('kesantrian.menu', [
            'menu' => 'master-kategori-raport',
            'unit' => $unit,
        ]);

        return $success
            ? $redirect->with('status', $message)
            : $redirect->with('error', $message);
    }
}
