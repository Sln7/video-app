<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class TrackApiActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $guestId = $request->header('X-Guest-Id') ?? $request->cookie('guest_id');

        $requestId = $request->header('X-Request-Id') ?? (string) Str::uuid();

        $response = $next($request);

        $user = $request->user();

        if (! $guestId && ! $user) {
            $guestId = (string) Str::uuid();
        }

        if (! $user && $guestId) {
            cookie()->queue(cookie('guest_id', $guestId, 60 * 24 * 365));
            $response->headers->set('X-Guest-Id', $guestId);
        }
        $response->headers->set('X-Request-Id', $requestId);

        try {
            $payload = $request->except(['password', 'password_confirmation', 'token']);

            ActivityLog::create([
                'user_id' => $user?->id,
                'guest_id' => $user ? null : $guestId,
                'actor_key' => $user ? 'user:'.$user->id : 'guest:'.$guestId,
                'action' => $request->method().' '.$request->path(),
                'method' => $request->method(),
                'path' => '/'.$request->path(),
                'status_code' => $response->getStatusCode(),
                'request_payload' => empty($payload) ? null : $payload,
                'query_params' => empty($request->query()) ? null : $request->query(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'request_id' => $requestId,
            ]);
        } catch (\Throwable) {
            // Do not break API requests if audit insertion fails.
        }

        return $response;
    }
}
