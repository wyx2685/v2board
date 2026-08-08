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
            'allow_insecure' => 'nullable|in:0,1',
            'server_name' => 'nullable',
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
            'allow_insecure.in' => __('Insecure setting format is invalid'),
            'tags.array' => __('Tag format is invalid'),
            'rate.required' => __('Traffic multiplier cannot be empty'),
            'rate.numeric' => __('Traffic multiplier format is invalid')
        ];
    }
}
