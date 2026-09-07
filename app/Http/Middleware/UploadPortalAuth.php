<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UploadPortalAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $webCheck = Auth::guard('upload-portal')->check();
        $apiCheck = Auth::check();
        if (!$webCheck && !$apiCheck) {
            if ($request->expectsJson() || $request->is('upload-portal/api/*')) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('upload-portal.login');
        }

        // // Check if user has only_upload flag
        // $user = Auth::guard('upload-portal')->user();
        // if (!$user->only_upload && !$user->role) {
        //     if ($request->expectsJson() || $request->is('upload-portal/api/*')) {
        //         return response()->json(['message' => 'Unauthorized access.'], 403);
        //     }
        //     return redirect()->route('upload-portal.login')->with('error', 'You do not have access to the upload portal.');
        // }

        return $next($request);
    }
}

