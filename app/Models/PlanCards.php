<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanCards extends Model
{
    protected $table = 'v2_plan_cards';
    protected $dateFormat = 'U';
    protected $guarded = ['id'];
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp'
    ];
}