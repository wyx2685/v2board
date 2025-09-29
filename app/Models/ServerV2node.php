<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServerV2node extends Model
{
    protected $table = 'v2_server_v2node';
    protected $dateFormat = 'U';
    protected $guarded = ['id'];
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
        'group_id' => 'array',
        'route_id' => 'array',
        'tags' => 'array',
        'tls_settings' => 'array',
        'network_settings' => 'array',
        'encryption_settings' => 'array',
        'padding_scheme' => 'array',
    ];
}
