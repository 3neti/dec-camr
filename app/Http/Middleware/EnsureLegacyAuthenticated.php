<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureLegacyAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has('loginID')) {
            return redirect('/')->with('fail', 'You Have to Login First');
        }

        return $next($request);
    }
}
