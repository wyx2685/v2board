<?php

namespace App\Plugins\Telegram;

use App\Services\TelegramService;
use App\Utils\CacheKey;
use Illuminate\Support\Facades\Cache;

abstract class Telegram {
    abstract protected function handle($message, $match);
    public $telegramService;

    public function __construct()
    {
        $this->telegramService = new TelegramService();
    }

    protected function localeFor($user = null): string
    {
        $locale = $user && isset($user->id)
            ? Cache::get(CacheKey::get('USER_LOCALE', $user->id))
            : app()->getLocale();
        $supported = array_keys((array) config('app.supported_locales', []));

        return is_string($locale) && in_array($locale, $supported, true)
            ? $locale
            : (string) config('app.default_locale', 'vi-VN');
    }

    protected function translateFor(string $key, array $replace = [], $user = null): string
    {
        return trans($key, $replace, $this->localeFor($user));
    }
}
