<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class StaffAccessService
{
    public function users($staffUserId): Builder
    {
        return User::query()
            ->where('invite_user_id', (int)$staffUserId)
            ->where('is_admin', 0)
            ->where('is_staff', 0);
    }

    public function findUser($staffUserId, $targetUserId): ?User
    {
        return $this->users($staffUserId)
            ->where('id', (int)$targetUserId)
            ->first();
    }

    public function tickets($staffUserId): Builder
    {
        return Ticket::query()->whereIn(
            'user_id',
            $this->users($staffUserId)->select('id')
        );
    }

    public function findTicket($staffUserId, $ticketId): ?Ticket
    {
        return $this->tickets($staffUserId)
            ->where('id', (int)$ticketId)
            ->first();
    }
}
