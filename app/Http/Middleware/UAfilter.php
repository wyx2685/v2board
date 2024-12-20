<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class UAfilter
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (defined('isWEBMAN') && isWEBMAN) {
            if(str_contains($request->header('Content-Type'), 'application/json')) {
                $phpInput = json_encode($_POST);
                $decodedData = json_decode($phpInput, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $request->merge($decodedData);
                }
            }
        }
        if (strpos($request->header('User-Agent'), 'MicroMessenger') !== false || strpos($request->header('User-Agent'), 'QQ/') !== false) {
            $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsupported Browser</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        h1 { color: #333; }
        p { color: #666; }
    </style>
</head>
<body>
    <h1>浏览器不支持</h1>
    <p>很抱歉，我们的页面在QQ和微信浏览器中无法正常访问。</p>
    <p>请点击右上方，选择在浏览器中打开。</p>
</body>
</html>
HTML;
            return response($html, 200)->header('Content-Type', 'text/html');
        }

        if (strpos($request->header('User-Agent'), 'python-requests')) {
            return response('', 200);
        }

        return $next($request);
    }
}
