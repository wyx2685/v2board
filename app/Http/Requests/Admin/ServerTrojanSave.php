<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ServerTrojanSave extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'show' => '',
            'name' => 'required',
            'group_id' => 'required|array',
            'route_id' => 'nullable|array',
            'parent_id' => 'nullable|integer',
            'host' => 'required',
            'port' => 'required',
            'server_port' => 'required',
            'network' => 'required',
            'network_settings' => 'nullable',
            'allow_insecure' => 'nullable|in:0,1,pincert',
            'pinned_peer_cert_sha256' => [
                'required_if:allow_insecure,pincert',
                'regex:/^([A-Fa-f0-9]{64}|[A-Fa-f0-9]{2}(:[A-Fa-f0-9]{2}){31})$/',
            ],
            'certificate_public_key_sha256' => 'nullable|string|max:128',
            'server_name' => 'nullable',
            'tags' => 'nullable|array',
            'rate' => 'required|numeric'
        ];
    }

    public function messages()
    {
        return [
            'name.required' => '节点名称不能为空',
            'group_id.required' => '权限组不能为空',
            'group_id.array' => '权限组格式不正确',
            'route_id.array' => '路由组格式不正确',
            'parent_id.integer' => '父节点格式不正确',
            'host.required' => '节点地址不能为空',
            'port.required' => '连接端口不能为空',
            'server_port.required' => '后端服务端口不能为空',
            'allow_insecure.in' => '允许不安全格式不正确',
            'tags.array' => '标签格式不正确',
            'rate.required' => '倍率不能为空',
            'rate.numeric' => '倍率格式不正确'
        ];
    }
}
