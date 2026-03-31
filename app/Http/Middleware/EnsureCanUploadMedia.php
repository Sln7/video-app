<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCanUploadMedia
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->canUploadMedia()) {
            return response()->json([
                'message' => 'Consumer users cannot upload media files.',
            ], 403);
        }

        return $next($request);
    }
}
