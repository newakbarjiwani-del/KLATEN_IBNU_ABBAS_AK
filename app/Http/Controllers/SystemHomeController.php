<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class SystemHomeController extends Controller
{
    public function __invoke(): View
    {
        return view('systems.home', [
            'account' => auth()->user(),
        ]);
    }
}
