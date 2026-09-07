<?php

namespace App\Http\Middleware;

use Closure;

class SecurityKeyCheckMiddleware
{
    public function handle($request, Closure $next)
    {
        $securityKey = $request->header('X-Security-Key');
            if ($securityKey !== config('app.security_key')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
