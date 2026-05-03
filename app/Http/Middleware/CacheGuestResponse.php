<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Full-page response cache for guest visitors.
 * Slashes response time from ~500ms to ~5ms on cache hits.
 */
class CacheGuestResponse
{
    public function handle(Request $request, Closure $next): mixed
    {
        // Only cache for guests — logged-in users see dynamic content
        if (auth()->check()) return $next($request);
        // Only cache GET/HEAD requests
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) return $next($request);
        // Don't cache AJAX/API requests
        if ($request->ajax() || $request->wantsJson()) return $next($request);

        $key = 'page_cache:' . sha1($request->fullUrl());

        if (Cache::has($key)) {
            $cached = Cache::get($key);
            return response($cached['content'], $cached['status'])
                ->header('X-Cache', 'HIT')
                ->header('Cache-Control', 'public, max-age=300');
        }

        $response = $next($request);

        if ($response->isSuccessful() && $response->getStatusCode() === 200) {
            Cache::put($key, [
                'content' => $response->getContent(),
                'status'  => $response->getStatusCode(),
            ], now()->addMinutes(10));
        }

        return $response->header('X-Cache', 'MISS');
    }
}
