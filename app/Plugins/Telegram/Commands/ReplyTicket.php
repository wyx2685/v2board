<?php

namespace App\Plugins\Telegram\Commands;

use App\Models\User;
use App\Plugins\Telegram\Telegram;
use App\Services\TicketService;

class ReplyTicket extends Telegram {
    public $regex = '/[#](.*)/';
    public $description = 'telegram.command_reply_ticket';

    public function handle($message, $match = []) {
        if (!$message->is_private) return;
        $this->replayTicket($message, $match[1]);
    }


    private function replayTicket($msg, $ticketId)
    {
        $user = User::where('telegram_id', $msg->chat_id)->first();
        if (!$user) {
            abort(500, $this->translateFor('telegram.user_does_not_exist'));
        }
        if (!$msg->text) return;
        if (!($user->is_admin || $user->is_staff)) return;
        $ticketService = new TicketService();
        $ticketService->replyByAdmin(
            $ticketId,
            $msg->text,
            $user->id
        );
        $telegramService = $this->telegramService;
        $telegramService->sendMessage(
            $msg->chat_id,
            $this->translateFor('telegram.ticket_reply_success', ['id' => $ticketId], $user)
        );
        $telegramService->sendTranslatedMessageWithAdmin(
            'telegram.ticket_reply_admin',
            [
                'id' => $ticketId,
                'email' => $user->email,
            ],
            true,
            ''
        );
    }
}
