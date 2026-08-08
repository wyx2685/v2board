<?php

namespace App\Http\Middleware;

use Closure;

class CORS
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        $origin = $this->normalizeOrigin($request->header('origin'));
        if (!$origin || !$this->isAllowed($origin, $request)) {
            return $response;
        }

        $response->header('Access-Control-Allow-Origin', $origin);
        $response->header('Access-Control-Allow-Methods', 'GET,POST,OPTIONS,HEAD');
        $response->header('Access-Control-Allow-Headers', 'Origin,Content-Type,Accept,Authorization,X-Requested-With,X-Request-With,Content-Language,Accept-Language');
        $response->header('Access-Control-Expose-Headers', 'Content-Language');
        $response->header('Access-Control-Allow-Credentials', 'true');
        $response->header('Access-Control-Max-Age', 10080);
        $response->headers->set('Vary', 'Origin', false);

        return $response;
    }

    private function isAllowed(string $origin, $request): bool
    {
        $allowedOrigins = (array)config('cors.allowed_origins', []);
        $allowedOrigins[] = $request->getSchemeAndHttpHost();
        $allowedOrigins[] = config('v2board.app_url');
        $allowedOrigins[] = config('app.url');

        foreach ($allowedOrigins as $allowedOrigin) {
            if ($allowedOrigin === '*') return true;
            if ($this->normalizeOrigin($allowedOrigin) === $origin) return true;
        }

        return false;
    }

    private function normalizeOrigin($origin): ?string
    {
        if (!is_string($origin) || trim($origin) === '') return null;

        $parts = parse_url(trim($origin));
        if (
            !is_array($parts)
            || empty($parts['scheme'])
            || empty($parts['host'])
            || !in_array(strtolower($parts['scheme']), ['http', 'https'], true)
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['query'])
            || isset($parts['fragment'])
        ) {
            return null;
        }

        $scheme = strtolower($parts['scheme']);
        $host = strtolower($parts['host']);
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';

        return $scheme . '://' . $host . $port;
    }
}
