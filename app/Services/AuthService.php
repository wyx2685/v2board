<?php

namespace App\Services;

use App\Utils\CacheKey;
use App\Utils\Helper;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;

class AuthService
{
    private $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function generateAuthData(Request $request)
    {
        $guid = Helper::guid();
        $authData = JWT::encode([
            'id' => $this->user->id,
            'session' => $guid,
        ], config('app.key'), 'HS256');
        self::addSession($this->user->id, $guid, [
            'ip' => $request->ip(),
            'login_at' => time(),
            'ua' => $request->userAgent(),
            'auth_data' => $authData
        ]);
        return [
            'token' => $this->user->token,
            'is_admin' => $this->user->is_admin,
            'auth_data' => $authData
        ];
    }

    public static function decryptAuthData($jwt)
    {
        try {
            $data = (array)JWT::decode($jwt, new Key(config('app.key'), 'HS256'));
            if (
                !isset($data['id'], $data['session'])
                || !self::checkSession($data['id'], $data['session'])
            ) {
                return false;
            }

            $user = User::select([
                'id',
                'email',
                'is_admin',
                'is_staff',
                'banned'
            ])->find($data['id']);

            if (!$user || (int)$user->banned === 1) return false;

            return $user->toArray();
        } catch (\Throwable $e) {
            return false;
        }
    }

    private static function checkSession($userId, $session)
    {
        $sessions = (array)Cache::get(CacheKey::get("USER_SESSIONS", $userId), []);
        if (!in_array($session, array_keys($sessions), true)) return false;
        return true;
    }

    private static function addSession($userId, $guid, $meta)
    {
        $cacheKey = CacheKey::get("USER_SESSIONS", $userId);
        $sessions = (array)Cache::get($cacheKey, []);
        $sessions[$guid] = $meta;
        if (!Cache::put(
            $cacheKey,
            $sessions
        )) return false;
        return true;
    }

    public function getSessions()
    {
        return (array)Cache::get(CacheKey::get("USER_SESSIONS", $this->user->id), []);
    }

    public function removeSession($sessionId)
    {
        $cacheKey = CacheKey::get("USER_SESSIONS", $this->user->id);
        $sessions = (array)Cache::get($cacheKey, []);
        $authData = isset($sessions[$sessionId]['auth_data'])
            ? $sessions[$sessionId]['auth_data']
            : null;
        unset($sessions[$sessionId]);
        if (!Cache::put(
            $cacheKey,
            $sessions
        )) return false;

        if ($authData) Cache::forget($authData);

        return true;
    }

    public function removeAllSession()
    {
        $cacheKey = CacheKey::get("USER_SESSIONS", $this->user->id);
        $sessions = (array)Cache::get($cacheKey, []);
        foreach ($sessions as $guid => $meta) {
            if (isset($meta['auth_data'])) {
                Cache::forget($meta['auth_data']);
            }
        }
        return Cache::forget($cacheKey);
    }
}
