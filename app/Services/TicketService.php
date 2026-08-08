<?php
namespace App\Services;


use App\Jobs\SendEmailJob;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Utils\CacheKey;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TicketService {
    public function reply($ticket, $message, $userId)
    {
        DB::beginTransaction();
        $ticketMessage = TicketMessage::create([
            'user_id' => $userId,
            'ticket_id' => $ticket->id,
            'message' => $message
        ]);
        if ($userId !== $ticket->user_id) {
            $ticket->reply_status = 1;
        } else {
            $ticket->reply_status = 0;
        }
        if (!$ticketMessage || !$ticket->save()) {
            DB::rollback();
            return false;
        }
        DB::commit();
        return $ticketMessage;
    }

    public function replyByAdmin($ticketId, $message, $userId):void
    {
        $ticket = Ticket::where('id', $ticketId)
            ->first();
        if (!$ticket) {
            abort(500, __('Ticket does not exist'));
        }
        
        DB::beginTransaction();
        $ticketMessage = TicketMessage::create([
            'user_id' => $userId,
            'ticket_id' => $ticket->id,
            'message' => $message
        ]);
        $ticket->status = 0;
        if ($userId !== $ticket->user_id) {
            $ticket->reply_status = 1;
        } else {
            $ticket->reply_status = 0;
        }
        $ticket->touch();
        if (!$ticketMessage || !$ticket->save()) {
            DB::rollback();
            abort(500, __('Ticket reply failed'));
        }
        DB::commit();
        $this->sendEmailNotify($ticket, $ticketMessage);
    }

    // 半小时内不再重复通知
    private function sendEmailNotify(Ticket $ticket, TicketMessage $ticketMessage)
    {
        $user = User::find($ticket->user_id);
        $cacheKey = 'ticket_sendEmailNotify_' . $ticket->user_id;
        if (!Cache::get($cacheKey)) {
            Cache::put($cacheKey, 1, 1800);
            $locale = Cache::get(CacheKey::get('USER_LOCALE', $user->id));
            $supported = array_keys((array) config('app.supported_locales', []));
            if (!is_string($locale) || !in_array($locale, $supported, true)) {
                $locale = (string) config('app.default_locale', 'vi-VN');
            }
            SendEmailJob::dispatch([
                'email' => $user->email,
                'locale' => $locale,
                'subject' => trans('mail.ticket_reply_subject', [
                    'app_name' => config('v2board.app_name', 'V2Board')
                ], $locale),
                'template_name' => 'notify',
                'template_value' => [
                    'name' => config('v2board.app_name', 'V2Board'),
                    'url' => config('v2board.app_url'),
                    'content' => trans('mail.ticket_reply_content', [
                        'subject' => $ticket->subject,
                        'message' => $ticketMessage->message
                    ], $locale)
                ]
            ]);
        }
    }
}
