<?php

namespace App\Http\Controllers;

use App\Akademik\AkademikMenu;
use App\Http\Controllers\Akademik\RaportAkademikController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AkademikDashboardController extends Controller
{
    public function index(): View
    {
        return view('akademik.home', [
            'account' => auth()->user(),
            'groups' => AkademikMenu::groups(),
            'current' => null,
        ]);
    }

    public function show(Request $request, string $menu): View|RedirectResponse
    {
        $item = AkademikMenu::find($menu);
        abort_if($item === null, 404);

        $raport = app(RaportAkademikController::class);

        return match ($item['slug']) {
            'upload-raport' => $raport->upload(),
            'data-raport' => $raport->data($request),
            default => abort(404),
        };
    }
}
