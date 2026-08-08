<?php

namespace App\Plugins\Telegram\Commands;

use App\Models\User;
use App\Plugins\Telegram\Telegram;
use App\Utils\Helper;

class Traffic extends Telegram {
    public $command = '/traffic';
    public $description = 'telegram.command_traffic';

    public function handle($message, $match = []) {
        $telegramService = $this->telegramService;
        if (!$message->is_private) return;
        $user = User::where('telegram_id', $message->chat_id)->first();
        if (!$user) {
            $telegramService->sendMessage(
                $message->chat_id,
                $this->translateFor('telegram.account_not_bound')
            );
            return;
        }
        $transferEnable = Helper::trafficConvert($user->transfer_enable);
        $up = Helper::trafficConvert($user->u);
        $down = Helper::trafficConvert($user->d);
        $remaining = Helper::trafficConvert($user->transfer_enable - ($user->u + $user->d));
        $text = $this->translateFor('telegram.traffic_status', [
            'total' => $transferEnable,
            'upload' => $up,
            'download' => $down,
            'remaining' => $remaining,
        ], $user);
        $telegramService->sendMessage($message->chat_id, $text);
    }
}
