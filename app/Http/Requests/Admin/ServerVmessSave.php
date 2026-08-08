<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ServerVmessSave extends FormRequest
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
            'tls' => 'required',
            'tags' => 'nullable|array',
            'rate' => 'required|numeric',
            'network' => 'required|in:tcp,kcp,ws,http,domainsocket,quic,grpc,httpupgrade,xhttp',
            'networkSettings' => 'nullable|array',
            'networkSettings.security' => 'nullable|in:auto,aes-128-gcm,chacha20-poly1305,none',
            'ruleSettings' => 'nullable|array',
            'tlsSettings' => 'nullable|array',
            'dnsSettings' => 'nullable|array'
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
            'tls.required' => __('TLS cannot be empty'),
            'tags.array' => __('Tag format is invalid'),
            'rate.required' => __('Traffic multiplier cannot be empty'),
            'rate.numeric' => __('Traffic multiplier format is invalid'),
            'network.required' => __('Transport protocol cannot be empty'),
            'network.in' => __('Transport protocol format is invalid'),
            'networkSettings.array' => __('Transport protocol settings format is invalid'),
            'networkSettings.security.in' => __('VMess encryption must be one of: auto, aes-128-gcm, chacha20-poly1305, none'),
            'ruleSettings.array' => __('Routing rules format is invalid'),
            'tlsSettings.array' => __('TLS settings format is invalid'),
            'dnsSettings.array' => __('DNS settings format is invalid')
        ];
    }
}
