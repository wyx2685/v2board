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
            $locale = htmlspecialchars(app()->getLocale(), ENT_QUOTES, 'UTF-8');
            $title = htmlspecialchars(__('Unsupported Browser'), ENT_QUOTES, 'UTF-8');
            $message = htmlspecialchars(__('This page is not available in the QQ or WeChat browser.'), ENT_QUOTES, 'UTF-8');
            $instruction = htmlspecialchars(__('Open the browser menu and choose to open this page in your system browser.'), ENT_QUOTES, 'UTF-8');
            $html = <<<HTML
<!DOCTYPE html>
<html lang="{$locale}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        h1 { color: #333; }
        p { color: #666; }
    </style>
</head>
<body>
    <h1>{$title}</h1>
    <p>{$message}</p>
    <p>{$instruction}</p>
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
