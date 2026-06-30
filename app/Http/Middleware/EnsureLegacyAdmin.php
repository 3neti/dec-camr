<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureLegacyAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $legacyUser = $request->attributes->get('legacyUser');

        if (! $legacyUser instanceof User || ! $legacyUser->isAdmin()) {
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized');
        }

        return $next($request);
    }
}
