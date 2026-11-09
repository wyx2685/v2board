<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    public function getAdminPermissionsAttribute($value)
    {
        if ($value === null) return null;
        $permissions = json_decode($value, true);
        return is_array($permissions) && array_values($permissions) === $permissions ? $permissions : [];
    }

    protected $table = 'v2_user';
    protected $dateFormat = 'U';
    protected $guarded = ['id'];
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
        'admin_permissions' => 'array'
    ];
}
