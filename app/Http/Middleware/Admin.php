<?php

namespace App\Http\Middleware;

use App\Services\AuthService;
use App\Services\AdminPermissionService;
use Closure;

class Admin
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
        if (!$authorization) abort(403, '未登录或登陆已过期');

        $user = AuthService::decryptAuthData($authorization, true);
        if (!$user || !$user['is_admin']) abort(403, '未登录或登陆已过期');
        if (!AdminPermissionService::allowsAction($user, $request->route()->getActionName(), $request)) {
            return response()->json(['message' => '没有此操作权限', 'permission_denied' => true], 403);
        }
        $request->merge([
            'user' => $user
        ]);
        $response = $next($request);
        if ($response->getStatusCode() === 403 && $response instanceof \Illuminate\Http\JsonResponse) {
            $response->setData(array_merge($response->getData(true), ['permission_denied' => true]));
        }
        return $response;
    }
}
