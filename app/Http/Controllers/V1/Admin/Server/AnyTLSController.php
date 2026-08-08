<?php

namespace App\Http\Controllers\V1\Admin\Server;

use App\Http\Controllers\Controller;
use App\Models\ServerAnytls;
use Illuminate\Http\Request;

class AnyTLSController extends Controller
{
    public function save(Request $request)
    {
        $params = $request->validate([
            'show' => '',
            'name' => 'required',
            'group_id' => 'required|array',
            'route_id' => 'nullable|array',
            'parent_id' => 'nullable|integer',
            'host' => 'required',
            'port' => 'required',
            'server_port' => 'required',
            'tags' => 'nullable|array',
            'rate' => 'required|numeric',
            'server_name' => 'nullable',
            'insecure' => 'required|in:0,1',
            'padding_scheme' => 'nullable',
        ]);

        if (isset($params['padding_scheme'])) {
            $params['padding_scheme'] = json_decode($params['padding_scheme']);
        }

        if ($request->input('id')) {
            $server = ServerAnytls::find($request->input('id'));
            if (!$server) {
                abort(500, __('Server does not exist'));
            }
            try {
                $server->update($params);
            } catch (\Exception $e) {
                abort(500, __('Save failed'));
            }
            return response([
                'data' => true
            ]);
        }

        if (!ServerAnytls::create($params)) {
            abort(500, __('Create failed'));
        }

        return response([
            'data' => true
        ]);
    }

    public function drop(Request $request)
    {
        if ($request->input('id')) {
            $server = ServerAnytls::find($request->input('id'));
            if (!$server) {
                abort(500, __('Node ID does not exist'));
            }
        }
        return response([
            'data' => $server->delete()
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'show' => 'in:0,1'
        ], [
            'show.in' => __('Display status format is invalid')
        ]);
        $params = $request->only([
            'show',
        ]);

        $server = ServerAnytls::find($request->input('id'));

        if (!$server) {
            abort(500, __('Server does not exist'));
        }
        try {
            $server->update($params);
        } catch (\Exception $e) {
            abort(500, __('Save failed'));
        }

        return response([
            'data' => true
        ]);
    }

    public function copy(Request $request)
    {
        $server = ServerAnytls::find($request->input('id'));
        $server->show = 0;
        if (!$server) {
            abort(500, __('Server does not exist'));
        }
        if (!ServerAnytls::create($server->toArray())) {
            abort(500, __('Copy failed'));
        }

        return response([
            'data' => true
        ]);
    }
}
