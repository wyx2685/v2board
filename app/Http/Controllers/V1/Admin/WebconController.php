<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\WebconSave;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class WebconController extends Controller
{
    public function fetch(Request $request)
    {
        $staffs = Staff::orderBy('id', 'DESC')->get();
    
        $staffs->transform(function ($staff) {
            $user = User::find($staff->user_id);
            $staff->email = $user ? $user->email : null;
            $staff->staff = $user ? $user->is_staff : 0;
            return $staff;
        });
        
        return response([
            'data' => $staffs
        ]);
    }
    

    public function save(WebconSave $request)
    {
        
        $data = $request->only([
            'email',
            'domain',
            'title',
            'description',
            'logo',
            'background_url',
            'custom_html'
        ]);
        $planIds = $request->input('plan_ids', []);
        $data['plan_id'] = array_map('intval', (array)$planIds);

        $user = User::where('email', $data['email'])->first();
        if (!$user) {
            abort(500, 'Email không tồn tại trong hệ thống!');
        }

        $data['user_id'] = $user->id;
        unset($data['email']);
        if (!$request->input('id')) {
            if (!Staff::create($data)) {
                abort(500, '保存失败');
            }
        } else {
            try {
                Staff::find($request->input('id'))->update($data);
            } catch (\Exception $e) {
                abort(500, '保存失败');
            }
        }
        return response([
            'data' => true
        ]);
    }



    public function show(Request $request)
    {
        if (empty($request->input('id'))) {
            abort(500, '参数有误');
        }
        $staff = Staff::find($request->input('id'));
        if (!$staff) {
            abort(500, '公告不存在');
        }
        $staff->status = $staff->status ? 0 : 1;
        if (!$staff->save()) {
            abort(500, '保存失败');
        }

        return response([
            'data' => true
        ]);
    }

    public function drop(Request $request)
    {
        if (empty($request->input('id'))) {
            abort(500, '参数错误');
        }
        $staff = Staff::find($request->input('id'));
        if (!$staff) {
            abort(500, '公告不存在');
        }
        if (!$staff->delete()) {
            abort(500, '删除失败');
        }
        return response([
            'data' => true
        ]);
    }
}
