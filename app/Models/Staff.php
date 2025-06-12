<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    protected $table = 'v2_staff';
    protected $dateFormat = 'U';
    protected $guarded = ['id'];
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
        'plan_id' => 'array',
    ];

    public function getPlanIdAttribute($value)
    {
        $arr = json_decode($value, true);
        if (!$arr || !is_array($arr)) {
            return [];
        }
        return array_map('intval', $arr);
    }
}
