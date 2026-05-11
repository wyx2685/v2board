<?php

namespace App\Http\Controllers\V1\Admin\Server;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServerTrojanSave;
use App\Http\Requests\Admin\ServerTrojanUpdate;
use App\Models\ServerTrojan;
use App\Services\ServerService;
use Illuminate\Http\Request;

class TrojanController extends Controller
{
    public function save(ServerTrojanSave $request)
    {
        $params = $request->validated();
        if (isset($params['network_settings']) && is_array($params['network_settings'])) {
            if (isset($params['network_settings']['fallback']) && !isset($params['network_settings']['fallbacks'])) {
                $params['network_settings']['fallbacks'] = $params['network_settings']['fallback'];
            }
            unset($params['network_settings']['fallback']);
            if (array_key_exists('fallbacks', $params['network_settings'])) {
                $fallbacks = $params['network_settings']['fallbacks'];
                if (is_string($fallbacks)) {
                    $fallbacks = trim($fallbacks);
                    if ($fallbacks === '') {
                        unset($params['network_settings']['fallbacks']);
                    } elseif (in_array(substr($fallbacks, 0, 1), ['[', '{'])) {
                        $fallbacks = json_decode($fallbacks, true);
                        if (json_last_error() !== JSON_ERROR_NONE || !is_array($fallbacks)) {
                            abort(500, 'fallbacks配置格式有误');
                        }
                        $params['network_settings']['fallbacks'] = isset($fallbacks['dest']) ? [$fallbacks] : $fallbacks;
                    } else {
                        $params['network_settings']['fallbacks'] = [['dest' => $fallbacks]];
                    }
                } elseif (is_array($fallbacks)) {
                    if (empty($fallbacks)) {
                        unset($params['network_settings']['fallbacks']);
                    } else {
                        $params['network_settings']['fallbacks'] = isset($fallbacks['dest']) ? [$fallbacks] : $fallbacks;
                    }
                } else {
                    abort(500, 'fallbacks配置格式有误');
                }
            }
        }
        if ($request->input('id')) {
            $server = ServerTrojan::find($request->input('id'));
            if (!$server) {
                abort(500, '服务器不存在');
            }
            try {
                $server->update($params);
            } catch (\Exception $e) {
                abort(500, '保存失败');
            }
            return response([
                'data' => true
            ]);
        }

        if (!ServerTrojan::create($params)) {
            abort(500, '创建失败');
        }

        return response([
            'data' => true
        ]);
    }

    public function drop(Request $request)
    {
        if ($request->input('id')) {
            $server = ServerTrojan::find($request->input('id'));
            if (!$server) {
                abort(500, '节点ID不存在');
            }
        }
        return response([
            'data' => $server->delete()
        ]);
    }

    public function update(ServerTrojanUpdate $request)
    {
        $params = $request->only([
            'show',
        ]);

        $server = ServerTrojan::find($request->input('id'));

        if (!$server) {
            abort(500, '该服务器不存在');
        }
        try {
            $server->update($params);
        } catch (\Exception $e) {
            abort(500, '保存失败');
        }

        return response([
            'data' => true
        ]);
    }

    public function copy(Request $request)
    {
        $server = ServerTrojan::find($request->input('id'));
        $server->show = 0;
        if (!$server) {
            abort(500, '服务器不存在');
        }
        if (!ServerTrojan::create($server->toArray())) {
            abort(500, '复制失败');
        }

        return response([
            'data' => true
        ]);
    }
}
