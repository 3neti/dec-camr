<?php

namespace App\Http\Middleware;

use App\Models\User;
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

        $loginId = (int) $request->session()->get('loginID');
        $legacyUser = User::query()->find($loginId);

        if (! $legacyUser) {
            $request->session()->forget('loginID');

            return redirect('/')->with('fail', 'You Have to Login First');
        }

        $request->attributes->set('legacyUser', $legacyUser);

        return $next($request);
    }
}
