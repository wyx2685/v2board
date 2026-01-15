<?php

namespace App\Http\Controllers\V1\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ServerService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ServerController extends Controller
{
    public function fetch(Request $request)
    {
        $user = User::find($request->user['id']);
        $servers = [];
        $userService = new UserService();
        if ($userService->isAvailable($user)) {
            $serverService = new ServerService();
            $servers = $serverService->getAvailableServers($user);
        }
        $filteredServers = array_map(function ($item) {
            return [
                'id'        => $item['id'] ?? null,
                'name'      => $item['name'] ?? null,
                'rate'      => $item['rate'] ?? null,
                'tags'      => $item['tags'] ?? [],
                'is_online' => $item['is_online'] ?? null,
                ];
        }, $servers);
        $eTag = sha1(json_encode(array_column($servers, 'cache_key')));
        if (strpos($request->header('If-None-Match'), $eTag) !== false ) {
            abort(304);
        }

        return response([
            'data' => $servers
        ])->header('ETag', "\"{$eTag}\"");
    }
}
