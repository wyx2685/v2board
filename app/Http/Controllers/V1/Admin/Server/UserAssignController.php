<?php

namespace App\Http\Controllers\V1\Admin\Server;

use App\Http\Controllers\Controller;
use App\Models\ServerUser;
use App\Models\User;
use Illuminate\Http\Request;

class UserAssignController extends Controller
{
    /**
     * 获取节点当前分配的用户列表
     * GET /server/userAssign/fetch?server_type=vless&server_id=1
     */
    public function fetch(Request $request)
    {
        $request->validate([
            'server_type' => 'required|string',
            'server_id'   => 'required|integer',
        ]);

        $userIds = ServerUser::where('server_type', $request->input('server_type'))
            ->where('server_id', $request->input('server_id'))
            ->pluck('user_id');

        $users = User::whereIn('id', $userIds)
            ->select(['id', 'email'])
            ->get();

        return response(['data' => $users]);
    }

    /**
     * 保存节点的用户分配（全量替换）
     * POST /server/userAssign/save
     * Body: { server_type, server_id, user_ids: [1, 2, 3] }
     */
    public function save(Request $request)
    {
        $params = $request->validate([
            'server_type' => 'required|string',
            'server_id'   => 'required|integer',
            'user_ids'    => 'nullable|array',
            'user_ids.*'  => 'integer',
        ]);

        $type = $params['server_type'];
        $id   = $params['server_id'];
        $userIds = array_unique($params['user_ids'] ?? []);

        // 全量替换：先删除旧记录，再批量插入新记录
        ServerUser::where('server_type', $type)->where('server_id', $id)->delete();

        if (!empty($userIds)) {
            $rows = array_map(fn($uid) => [
                'server_type' => $type,
                'server_id'   => $id,
                'user_id'     => $uid,
            ], $userIds);
            ServerUser::insert($rows);
        }

        return response(['data' => true]);
    }

    /**
     * 搜索用户（供前端选择器使用）
     * GET /server/userAssign/searchUsers?keyword=xxx
     */
    public function searchUsers(Request $request)
    {
        $keyword = $request->input('keyword', '');

        $users = User::where('email', 'like', "%{$keyword}%")
            ->select(['id', 'email'])
            ->limit(20)
            ->get();

        return response(['data' => $users]);
    }
}
