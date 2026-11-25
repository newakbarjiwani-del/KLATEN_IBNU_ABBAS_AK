<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Tahfidz\HafalanController;
use App\Tahfidz\TahfidzMenu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TahfidzDashboardController extends Controller
{
    public function index(): View
    {
        return view('tahfidz.home', [
            'account' => auth()->user(),
            'groups' => TahfidzMenu::groups(),
            'current' => null,
        ]);
    }

    public function show(Request $request, string $menu): View|RedirectResponse
    {
        if ($menu === 'tahfidz') {
            return redirect()->route('tahfidz.menu', 'data-setoran');
        }

        if (in_array($menu, ['murajaah', 'setoran'], true)) {
            return redirect()->route('tahfidz.menu', 'upload-tahfidz');
        }

        $item = TahfidzMenu::find($menu);
        abort_if($item === null, 404);

        $hafalan = app(HafalanController::class);

        return match ($item['slug']) {
            'upload-tahfidz' => $hafalan->impor($request, $item, 'upload-tahfidz'),
            'laporan-capaian' => $hafalan->laporan($request, $item),
            'setoran-lama' => $hafalan->lama($request, $item),
            default => $hafalan->gabungan($request, $item),
        };
    }
}
