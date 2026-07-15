<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $table = 'v2_plan';
    protected $dateFormat = 'U';
    protected $guarded = ['id'];
    protected $casts = [
        'group_id' => 'array',
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp'
    ];
}
