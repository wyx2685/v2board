<?php
namespace App\Services;
use App\Utils\CacheKey;
use App\Utils\Helper;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
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
            if (!Cache::has($jwt)) {
                $data = (array)JWT::decode($jwt, new Key(config('app.key'), 'HS256'));
                if (!self::checkSession($data['id'], $data['session'])) return false;
                $user = User::select([
                    'id',
                    'email',
                    'is_admin',
                    'is_staff'
                ])
                    ->find($data['id']);
                if (!$user) return false;
                Cache::put($jwt, $user->toArray(), 3600);
            }
            return Cache::get($jwt);
        } catch (\Exception $e) {
            return false;
        }
    }

    private static function checkSession($userId, $session)
    {
        $sessions = self::getSessionsFromRedis($userId);
        if (!in_array($session, array_keys($sessions))) return false;
        return true;
    }

    private static function addSession($userId, $guid, $meta)
    {
        $sessions = self::getSessionsFromRedis($userId);
        $sessions[$guid] = $meta;
        return self::saveSessionsToRedis($userId, $sessions);
    }

    // مستقیم از Redis DB 2 بخون (بدون Cache facade)
    private static function getSessionsFromRedis($userId)
    {
        $key = CacheKey::get("USER_SESSIONS", $userId);
        $data = \Illuminate\Support\Facades\Redis::connection('session')->get($key);
        return $data ? unserialize($data) : [];
    }

    // مستقیم در Redis DB 2 ذخیره کن (بدون Cache facade)
    private static function saveSessionsToRedis($userId, $sessions)
    {
        $key = CacheKey::get("USER_SESSIONS", $userId);
        return \Illuminate\Support\Facades\Redis::connection('session')->set($key, serialize($sessions));
    }

    // حذف از Redis DB 2
    private static function deleteSessionsFromRedis($userId)
    {
        $key = CacheKey::get("USER_SESSIONS", $userId);
        return \Illuminate\Support\Facades\Redis::connection('session')->del($key);
    }

    public function getSessions()
    {
        return self::getSessionsFromRedis($this->user->id);
    }

    public function removeSession($sessionId)
    {
        $sessions = self::getSessionsFromRedis($this->user->id);
        unset($sessions[$sessionId]);
        return self::saveSessionsToRedis($this->user->id, $sessions);
    }

    public function removeAllSession()
    {
        $sessions = self::getSessionsFromRedis($this->user->id);
        foreach ($sessions as $guid => $meta) {
            if (isset($meta['auth_data'])) {
                Cache::forget($meta['auth_data']);
            }
        }
        return self::deleteSessionsFromRedis($this->user->id);
    }
}
