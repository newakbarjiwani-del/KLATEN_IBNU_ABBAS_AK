<?php

namespace App\Http\Controllers;

use App\Models\CyberKey;
use App\Portal\PortalNav;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PortalController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $account = auth()->user();

        if (! $account instanceof CyberKey || $account->homeRoute() === null) {
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            return redirect()->route('login');
        }

        if (count($account->accessIds()) < 2) {
            return redirect()->route($account->homeRoute());
        }

        return view('portal.home', [
            'account' => $account,
            'sections' => PortalNav::sections($account),
            'groups' => [],
            'current' => null,
        ]);
    }
}
