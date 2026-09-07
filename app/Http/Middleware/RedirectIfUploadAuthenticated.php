<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfUploadAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('upload-portal')->check() || Auth::check()) {
            return redirect()->route('upload-portal.index');
        }
        return $next($request);
    }
}
