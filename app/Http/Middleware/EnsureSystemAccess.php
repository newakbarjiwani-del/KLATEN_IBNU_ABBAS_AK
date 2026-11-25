<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSystemAccess
{
    public function handle(Request $request, Closure $next, string $kel): Response
    {
        $account = $request->user();

        if ($account === null || $account->homeRoute() === null) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login');
        }

        if (! $account->allows($kel)) {
            return redirect()->route($account->homeRoute());
        }

        return $next($request);
    }
}
