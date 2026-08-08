<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use App\Utils\CacheKey;
use App\Utils\Helper;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;

class Client
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
        $token = $request->input('token');
        if (empty($token)) {
            abort(403, __('Token error'));
        }
        $submethod = (int)config('v2board.show_subscribe_method', 0);
        switch ($submethod) {
            case 0:
                break;
            case 1:
                if (!Cache::has("otpn_{$token}")) {
                    abort(403, __('Token error'));
                }
                $usertoken = Cache::pull("otpn_{$token}");
                Cache::forget("otp_{$usertoken}");
                $token = $usertoken;
                break;
            case 2:
                $usertoken = Cache::get("totp_{$token}");
                if (!$usertoken) {
                    $timestep = (int)config('v2board.show_subscribe_expire', 5) * 60;
                    $counter = floor(time() / $timestep);
                    $counterBytes = pack('N*', 0) . pack('N*', $counter);
                    $idhash = Helper::base64DecodeUrlSafe($token);
                    if (strpos($idhash, ':') === false) {
                        abort(403, __('Token error'));
                    }
                    $parts = explode(':', $idhash, 2);
                    [$userid, $clienthash] = $parts;
                    if (!$userid || !$clienthash) {
                        abort(403, __('Token error'));
                    }
                    $user = User::where('id', $userid)->select('token')->first();
                    if (!$user) {
                        abort(403, __('Token error'));
                    }
                    $usertoken = $user->token;
                    $hash = hash_hmac('sha1', $counterBytes, $usertoken, false);
                    if ($clienthash !== $hash) {
                        abort(403, __('Token error'));
                    }
                    Cache::put("totp_{$token}", $usertoken, $timestep);
                }
                $token = $usertoken;
                break;
            default:
                break;
        }
        $user = User::where('token', $token)->first();
        if (!$user) {
            abort(403, __('Token error'));
        }
        $this->applyRememberedLocale($request, $user);
        $request->merge([
            'user' => $user
        ]);
        return $next($request);
    }

    private function applyRememberedLocale($request, User $user): void
    {
        if (
            trim((string) $request->header('Content-Language', '')) !== '' ||
            trim((string) $request->header('Accept-Language', '')) !== ''
        ) {
            return;
        }

        $locale = Cache::get(CacheKey::get('USER_LOCALE', $user->id));
        $supported = array_keys((array) config('app.supported_locales', []));
        if (!is_string($locale) || !in_array($locale, $supported, true)) {
            return;
        }

        App::setLocale($locale);
        if (method_exists($request, 'setLocale')) {
            $request->setLocale($locale);
        }
    }
}
