<?php

namespace App\Plugins\Telegram\Commands;

use App\Models\User;
use App\Plugins\Telegram\Telegram;
use App\Utils\Helper;
use Illuminate\Support\Facades\Cache;

class Bind extends Telegram {
    public $command = '/bind';
    public $description = 'telegram.command_bind';

    public function handle($message, $match = []) {
        if (!$message->is_private) return;
        if (!isset($message->args[0])) {
            abort(500, $this->translateFor('telegram.bind_usage'));
        }
        $queryString = parse_url($message->args[0], PHP_URL_QUERY);
        if (!is_string($queryString)) {
            abort(500, $this->translateFor('telegram.invalid_subscribe_url'));
        }
        parse_str($queryString, $query);
        $token = isset($query['token']) && is_string($query['token'])
            ? $query['token']
            : null;
        if (!$token) {
            abort(500, $this->translateFor('telegram.invalid_subscribe_url'));
        }
        $submethod = (int)config('v2board.show_subscribe_method', 0);
        switch ($submethod) {
            case 0:
                break;
            case 1:
                if (!Cache::has("otpn_{$token}")) {
                    abort(403, $this->translateFor('telegram.invalid_token'));
                }
                $usertoken = Cache::get("otpn_{$token}");
                $token = $usertoken;
                break;
            case 2:
                $usertoken = Cache::get("totp_{$token}");
                if (!$usertoken) {
                    $timestep = (int)config('v2board.show_subscribe_expire', 5) * 60;
                    $counter = floor(time() / $timestep);
                    $counterBytes = pack('N*', 0) . pack('N*', $counter);
                    $idhash = Helper::base64DecodeUrlSafe($token);
                    $parts = explode(':', $idhash, 2);
                    $userid = $parts[0] ?? null;
                    $clienthash = $parts[1] ?? null;
                    if (!$userid || !$clienthash) {
                        abort(403, $this->translateFor('telegram.invalid_token'));
                    }
                    $tokenOwner = User::where('id', $userid)->select('id', 'token')->first();
                    if (!$tokenOwner) {
                        abort(403, $this->translateFor('telegram.invalid_token'));
                    }
                    $usertoken = $tokenOwner->token;
                    $hash = hash_hmac('sha1', $counterBytes, $usertoken, false);
                    if ($clienthash !== $hash) {
                        abort(403, $this->translateFor('telegram.invalid_token', [], $tokenOwner));
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
            abort(500, $this->translateFor('telegram.user_does_not_exist'));
        }
        if ($user->telegram_id) {
            abort(500, $this->translateFor('telegram.already_bound', [], $user));
        }
        $user->telegram_id = $message->chat_id;
        if (!$user->save()) {
            abort(500, $this->translateFor('telegram.setting_failed', [], $user));
        }
        $telegramService = $this->telegramService;
        $telegramService->sendMessage(
            $message->chat_id,
            $this->translateFor('telegram.bind_success', [], $user)
        );
    }
}
