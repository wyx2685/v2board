<?php

namespace App\Http\Middleware;

use App\Services\AuthService;
use App\Utils\CacheKey;
use Closure;
use Illuminate\Support\Facades\Cache;

class Staff
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $authorization = $request->input('auth_data') ?? $request->header('authorization');
        if (!$authorization) abort(403, __('Authentication required or session has expired'));

        $user = AuthService::decryptAuthData($authorization);
        if (!$user || !$user['is_staff']) abort(403, __('Authentication required or session has expired'));
        $request->merge([
            'user' => $user
        ]);
        Cache::put(
            CacheKey::get('USER_LOCALE', $user['id']),
            app()->getLocale(),
            180 * 24 * 60 * 60
        );
        return $next($request);
    }
}
