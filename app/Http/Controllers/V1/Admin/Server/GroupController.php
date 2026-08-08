<?php

namespace App\Http\Controllers\V1\Admin\Server;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\ServerGroup;
use App\Models\ServerVmess;
use App\Models\ServerVless;
use App\Models\User;
use App\Services\ServerService;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function fetch(Request $request)
    {
        if ($request->input('group_id')) {
            return response([
                'data' => [ServerGroup::find($request->input('group_id'))]
            ]);
        }
        $serverGroups = ServerGroup::get();
        $serverService = new ServerService();
        $servers = $serverService->getAllServers();
        foreach ($serverGroups as $k => $v) {
            $serverGroups[$k]['user_count'] = User::where('group_id', $v['id'])->count();
            $serverGroups[$k]['server_count'] = 0;
            foreach ($servers as $server) {
                if (in_array($v['id'], $server['group_id'])) {
                    $serverGroups[$k]['server_count'] = $serverGroups[$k]['server_count']+1;
                }
            }
        }
        return response([
            'data' => $serverGroups
        ]);
    }

    public function save(Request $request)
    {
        if (empty($request->input('name'))) {
            abort(500, __('Group name cannot be empty'));
        }

        if ($request->input('id')) {
            $serverGroup = ServerGroup::find($request->input('id'));
        } else {
            $serverGroup = new ServerGroup();
        }

        $serverGroup->name = $request->input('name');
        return response([
            'data' => $serverGroup->save()
        ]);
    }

    public function drop(Request $request)
    {
        if ($request->input('id')) {
            $serverGroup = ServerGroup::find($request->input('id'));
            if (!$serverGroup) {
                abort(500, __('Group does not exist'));
            }
        }

        $servers = ServerVmess::all();
        foreach ($servers as $server) {
            if (in_array($request->input('id'), $server->group_id)) {
                abort(500, __('This group is used by servers and cannot be deleted'));
            }
        }

        $servers = ServerVless::all();
        foreach ($servers as $server) {
            if (in_array($request->input('id'), $server->group_id)) {
                abort(500, __('This group is used by servers and cannot be deleted'));
            }
        }

        if (Plan::where('group_id', $request->input('id'))->first()) {
            abort(500, __('This group is used by subscription plans and cannot be deleted'));
        }
        if (User::where('group_id', $request->input('id'))->first()) {
            abort(500, __('This group is used by users and cannot be deleted'));
        }
        return response([
            'data' => $serverGroup->delete()
        ]);
    }
}
