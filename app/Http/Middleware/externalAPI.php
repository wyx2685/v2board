<?php

namespace App\Http\Middleware;

use App\Utils\CacheKey;
use Closure;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class externalAPI
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
        $token = $request->input('Email');
        if (empty($token)) {
            abort(403, 'Email is null');
        }
        $user = User::where('email', $token)->first();
        if (!$user) {
            abort(403, 'Email is error');
        }
        $request->merge([
            'user' => $user
        ]);
        return $next($request);
    }
}