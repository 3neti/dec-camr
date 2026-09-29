<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireWalkthroughAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('camr.walkthrough_access.enabled')) {
            return $next($request);
        }

        $username = (string) config('camr.walkthrough_access.username');
        $password = (string) config('camr.walkthrough_access.password');

        if ($username !== '' && $password !== ''
            && hash_equals($username, (string) $request->getUser())
            && hash_equals($password, (string) $request->getPassword())) {
            return $next($request);
        }

        return response('Walkthrough access required.', 401)
            ->header('WWW-Authenticate', 'Basic realm="CAMR Walkthrough"')
            ->header('Cache-Control', 'no-store');

    }
}
