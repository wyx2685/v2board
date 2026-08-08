<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;

class Language
{
    public function handle($request, Closure $next)
    {
        // Long-running workers (for example Webman) reuse the application
        // instance, so the locale must be reset at the start of every request.
        $supported = array_keys((array) config('app.supported_locales', []));
        $default = (string) config(
            'app.default_locale',
            config('app.locale', 'vi-VN')
        );
        if (!in_array($default, $supported, true)) {
            $default = 'vi-VN';
        }

        $locale = $default;
        App::setLocale($locale);

        $contentLanguage = $request->header('Content-Language');
        $contentLocale = null;
        if (is_string($contentLanguage) && $contentLanguage !== '') {
            $contentLocale = $this->matchLocale($contentLanguage, $supported);
            $locale = $contentLocale ?? $locale;
        } else {
            $locale = $this->localeFromAcceptLanguage(
                (string) $request->header('Accept-Language', ''),
                $supported
            ) ?? $locale;
        }

        // An invalid Content-Language should not suppress a valid browser
        // preference in Accept-Language.
        if ($contentLocale === null && is_string($contentLanguage) && $contentLanguage !== '') {
            $locale = $this->localeFromAcceptLanguage(
                (string) $request->header('Accept-Language', ''),
                $supported
            ) ?? $locale;
        }

        App::setLocale($locale);
        if (method_exists($request, 'setLocale')) {
            $request->setLocale($locale);
        }

        $response = $next($request);
        $responseLocale = (string) App::getLocale();
        if (!in_array($responseLocale, $supported, true)) {
            $responseLocale = $locale;
            App::setLocale($responseLocale);
        }
        $response->headers->set('Content-Language', $responseLocale);

        return $response;
    }

    private function localeFromAcceptLanguage(string $header, array $supported): ?string
    {
        if ($header === '') {
            return null;
        }

        $preferences = [];
        foreach (explode(',', $header) as $position => $part) {
            $segments = array_map('trim', explode(';', $part));
            $tag = array_shift($segments);
            $quality = 1.0;

            foreach ($segments as $segment) {
                if (preg_match('/^q=([01](?:\.\d{0,3})?)$/i', $segment, $matches)) {
                    $quality = (float) $matches[1];
                }
            }

            if ($tag !== '' && $tag !== '*' && $quality > 0) {
                $preferences[] = [
                    'tag' => $tag,
                    'quality' => $quality,
                    'position' => $position,
                ];
            }
        }

        usort($preferences, static function (array $left, array $right): int {
            if ($left['quality'] === $right['quality']) {
                return $left['position'] <=> $right['position'];
            }

            return $left['quality'] < $right['quality'] ? 1 : -1;
        });

        foreach ($preferences as $preference) {
            $locale = $this->matchLocale($preference['tag'], $supported);
            if ($locale !== null) {
                return $locale;
            }
        }

        return null;
    }

    private function matchLocale(string $value, array $supported): ?string
    {
        $tag = strtolower(str_replace('_', '-', trim(explode(',', $value)[0])));
        if ($tag === '') {
            return null;
        }

        $aliases = [
            'vi' => 'vi-VN',
            'vi-vn' => 'vi-VN',
            'vn' => 'vi-VN',
            'ru' => 'ru-RU',
            'ru-ru' => 'ru-RU',
            'en' => 'en-US',
            'en-us' => 'en-US',
            'zh' => 'zh-CN',
            'zh-cn' => 'zh-CN',
            'zh-hans' => 'zh-CN',
            'zh-sg' => 'zh-CN',
            'cn' => 'zh-CN',
        ];

        $candidate = $aliases[$tag] ?? null;
        if ($candidate !== null && in_array($candidate, $supported, true)) {
            return $candidate;
        }

        foreach ($supported as $locale) {
            if (strtolower(str_replace('_', '-', $locale)) === $tag) {
                return $locale;
            }
        }

        // Region variants such as en-GB and vi-US use the supported language.
        $language = explode('-', $tag)[0];

        $candidate = $aliases[$language] ?? null;

        return $candidate !== null && in_array($candidate, $supported, true)
            ? $candidate
            : null;
    }
}
