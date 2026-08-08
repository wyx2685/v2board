<?php

namespace App\Http\Controllers\V1\Staff;

use App\Http\Controllers\Controller;
use App\Models\TicketMessage;
use App\Services\StaffAccessService;
use App\Services\TicketService;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    private $access;

    public function __construct(StaffAccessService $access)
    {
        $this->access = $access;
    }

    public function fetch(Request $request)
    {
        if ($request->input('id')) {
            $ticket = $this->access->findTicket(
                $request->input('user.id'),
                $request->input('id')
            );
            if (!$ticket) {
                abort(500, __('Ticket does not exist'));
            }
            $ticket['message'] = TicketMessage::where('ticket_id', $ticket->id)->get();
            for ($i = 0; $i < count($ticket['message']); $i++) {
                if ($ticket['message'][$i]['user_id'] !== $ticket->user_id) {
                    $ticket['message'][$i]['is_me'] = true;
                } else {
                    $ticket['message'][$i]['is_me'] = false;
                }
            }
            return response([
                'data' => $ticket
            ]);
        }
        $current = $request->input('current') ? $request->input('current') : 1;
        $pageSize = $request->input('pageSize') >= 10 ? $request->input('pageSize') : 10;
        $model = $this->access->tickets($request->input('user.id'))
            ->orderBy('created_at', 'DESC');
        if ($request->input('status') !== NULL) {
            $model->where('status', $request->input('status'));
        }
        $total = $model->count();
        $res = $model->forPage($current, $pageSize)
            ->get();
        return response([
            'data' => $res,
            'total' => $total
        ]);
    }

    public function reply(Request $request)
    {
        if (empty($request->input('id'))) {
            abort(500, __('Invalid parameter'));
        }
        if (empty($request->input('message'))) {
            abort(500, __('Message cannot be empty'));
        }
        $ticket = $this->access->findTicket(
            $request->input('user.id'),
            $request->input('id')
        );
        if (!$ticket) {
            abort(500, __('Ticket does not exist'));
        }
        $ticketService = new TicketService();
        $ticketService->replyByAdmin(
            $ticket->id,
            $request->input('message'),
            $request->user['id']
        );
        return response([
            'data' => true
        ]);
    }

    public function close(Request $request)
    {
        if (empty($request->input('id'))) {
            abort(500, __('Invalid parameter'));
        }
        $ticket = $this->access->findTicket(
            $request->input('user.id'),
            $request->input('id')
        );
        if (!$ticket) {
            abort(500, __('Ticket does not exist'));
        }
        $ticket->status = 1;
        if (!$ticket->save()) {
            abort(500, __('Close failed'));
        }
        return response([
            'data' => true
        ]);
    }
}
