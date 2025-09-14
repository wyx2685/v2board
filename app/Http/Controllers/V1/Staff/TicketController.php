<?php

namespace App\Http\Controllers\V1\Staff;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Services\TicketService;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function fetch(Request $request)
    {
        $staffUserId = $request->input('user.id');
        
        if ($request->input('id')) {
            // Get specific ticket - check if user belongs to this staff
            $ticket = Ticket::where('id', $request->input('id'))
                ->whereHas('user', function($query) use ($staffUserId) {
                    $query->where('invite_user_id', $staffUserId);
                })
                ->first();
            if (!$ticket) {
                abort(500, 'Ticket không tồn tại hoặc bạn không có quyền truy cập');
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
        
        // Get tickets list - only show tickets from users invited by this staff
        $current = $request->input('current') ? $request->input('current') : 1;
        $pageSize = $request->input('pageSize') >= 10 ? $request->input('pageSize') : 10;
        $model = Ticket::orderBy('created_at', 'DESC')
            ->whereHas('user', function($query) use ($staffUserId) {
                $query->where('invite_user_id', $staffUserId);
            });
            
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
            abort(500, 'Tham số không hợp lệ');
        }
        if (empty($request->input('message'))) {
            abort(500, 'Tin nhắn không thể để trống');
        }
        
        $staffUserId = $request->input('user.id');
        
        // Check if ticket belongs to users invited by this staff
        $ticket = Ticket::where('id', $request->input('id'))
            ->whereHas('user', function($query) use ($staffUserId) {
                $query->where('invite_user_id', $staffUserId);
            })
            ->first();
            
        if (!$ticket) {
            abort(500, 'Ticket không tồn tại hoặc bạn không có quyền truy cập');
        }
        
        $ticketService = new TicketService();
        $ticketService->replyByAdmin(
            $request->input('id'),
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
            abort(500, 'Tham số không hợp lệ');
        }
        
        $staffUserId = $request->input('user.id');
        
        // Check if ticket belongs to users invited by this staff
        $ticket = Ticket::where('id', $request->input('id'))
            ->whereHas('user', function($query) use ($staffUserId) {
                $query->where('invite_user_id', $staffUserId);
            })
            ->first();
            
        if (!$ticket) {
            abort(500, 'Ticket không tồn tại hoặc bạn không có quyền truy cập');
        }
        
        $ticket->status = 1;
        if (!$ticket->save()) {
            abort(500, 'Đóng ticket thất bại');
        }
        return response([
            'data' => true
        ]);
    }
}
