<?php

namespace App\Http\Middleware;

use Closure;

class CORS
{
    public function handle($request, Closure $next)
    {
        $origin = $request->header('origin');
        if (empty($origin)) {
            $referer = $request->header('referer');
            if (!empty($referer) && preg_match("/^((https|http):\/\/)?([^\/]+)/i", $referer, $matches)) {
                $origin = $matches[0];
            }
        }
        $response = $next($request);
        
        // 保存原有的 Content-Type（如果存在）
        $originalContentType = $response->headers->get('Content-Type');
        
        $response->header('Access-Control-Allow-Origin', trim($origin, '/'));
        $response->header('Access-Control-Allow-Methods', 'GET,POST,OPTIONS,HEAD');
        $response->header('Access-Control-Allow-Headers', 'Origin,Content-Type,Accept,Authorization,X-Request-With');
        $response->header('Access-Control-Allow-Credentials', 'true');
        $response->header('Access-Control-Max-Age', 10080);
        
        // 恢复原有的 Content-Type（如果被覆盖了）
        if ($originalContentType && $originalContentType !== 'text/html; charset=UTF-8') {
            $response->header('Content-Type', $originalContentType);
        }

        return $response;
    }
}
