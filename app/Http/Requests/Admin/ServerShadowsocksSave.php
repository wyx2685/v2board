<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ServerShadowsocksSave extends FormRequest
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
            'parent_id' => 'nullable|integer',
            'route_id' => 'nullable|array',
            'host' => 'required',
            'port' => 'required',
            'server_port' => 'required',
            'cipher' => 'required|in:aes-128-gcm,aes-192-gcm,aes-256-gcm,chacha20-ietf-poly1305,2022-blake3-aes-128-gcm,2022-blake3-aes-256-gcm',
            'obfs' => 'nullable|in:http',
            'obfs_settings' => 'nullable|array',
            'tags' => 'nullable|array',
            'rate' => 'required|numeric'
        ];
    }

    public function messages()
    {
        return [
            'name.required' => __('Server name cannot be empty'),
            'group_id.required' => __('Permission group cannot be empty'),
            'group_id.array' => __('Permission group format is invalid'),
            'route_id.array' => __('Routing group format is invalid'),
            'parent_id.integer' => __('Parent server format is invalid'),
            'host.required' => __('Server address cannot be empty'),
            'port.required' => __('Connection port cannot be empty'),
            'server_port.required' => __('Backend service port cannot be empty'),
            'cipher.required' => __('Encryption method cannot be empty'),
            'tags.array' => __('Tag format is invalid'),
            'rate.required' => __('Traffic multiplier cannot be empty'),
            'rate.numeric' => __('Traffic multiplier format is invalid'),
            'obfs.in' => __('Obfuscation format is invalid'),
            'obfs_settings.array' => __('Obfuscation settings format is invalid')
        ];
    }
}
