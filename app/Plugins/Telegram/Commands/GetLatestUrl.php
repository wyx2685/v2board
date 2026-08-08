<?php

namespace App\Plugins\Telegram\Commands;

use App\Models\User;
use App\Plugins\Telegram\Telegram;

class GetLatestUrl extends Telegram {
    public $command = '/getlatesturl';
    public $description = 'telegram.command_latest_url';

    public function handle($message, $match = []) {
        $telegramService = $this->telegramService;
        $user = User::where('telegram_id', $message->chat_id)->first();
        $text = $this->translateFor('telegram.latest_url', [
            'app_name' => config('v2board.app_name', 'V2Board'),
            'url' => config('v2board.app_url'),
        ], $user);
        $telegramService->sendMessage($message->chat_id, $text);
    }
}
