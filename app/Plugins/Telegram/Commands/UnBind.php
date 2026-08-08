<?php

namespace App\Plugins\Telegram\Commands;

use App\Models\User;
use App\Plugins\Telegram\Telegram;

class UnBind extends Telegram {
    public $command = '/unbind';
    public $description = 'telegram.command_unbind';

    public function handle($message, $match = []) {
        if (!$message->is_private) return;
        $user = User::where('telegram_id', $message->chat_id)->first();
        $telegramService = $this->telegramService;
        if (!$user) {
            $telegramService->sendMessage(
                $message->chat_id,
                $this->translateFor('telegram.account_not_bound')
            );
            return;
        }
        $user->telegram_id = NULL;
        if (!$user->save()) {
            abort(500, $this->translateFor('telegram.unbind_failed', [], $user));
        }
        $telegramService->sendMessage(
            $message->chat_id,
            $this->translateFor('telegram.unbind_success', [], $user)
        );
    }
}
