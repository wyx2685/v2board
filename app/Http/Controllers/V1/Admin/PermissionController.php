<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AdminPermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PermissionController extends Controller
{
    public function current(Request $request)
    {
        return response(['data' => AdminPermissionService::access($request->user)]);
    }

    public function fetch(Request $request)
    {
        $admins = User::where('is_admin', 1)
            ->select(['id', 'email', 'banned', 'is_admin', 'admin_permissions'])
            ->orderBy('id')->get();
        return response(['data' => $admins]);
    }

    public function save(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|integer',
            'is_super_admin' => 'required|boolean',
            'permissions' => 'present|array',
            'permissions.*' => ['string', 'distinct', Rule::in(array_keys(config('admin_permissions.labels')))],
        ]);

        DB::transaction(function () use ($request, $data) {
            // Serialize authorization changes and recheck the actor after taking the lock.
            $admins = User::where('is_admin', 1)->orderBy('id')->lockForUpdate()->get();
            $actor = $admins->firstWhere('id', $request->user['id']);
            if (!$actor || $actor->banned || !AdminPermissionService::isSuper($actor->toArray())) abort(403, '只有完整权限管理员可以分配权限');
            $target = $admins->firstWhere('id', (int)$data['id']);
            if (!$target) abort(422, '管理员不存在，请先将用户设为管理员');
            if ($target->id === $actor->id && !$data['is_super_admin']) abort(422, '不能撤销自己的完整管理权限');
            $before = $target->admin_permissions;
            $target->admin_permissions = $data['is_super_admin'] ? null : AdminPermissionService::normalize($data['permissions']);
            $target->save();
            info('管理员权限变更', [
                'actor_id' => $actor->id, 'target_id' => $target->id,
                'before' => $before, 'after' => $target->admin_permissions,
            ]);
        });

        return response(['data' => true]);
    }
}
