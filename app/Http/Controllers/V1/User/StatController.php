<?php

namespace App\Http\Controllers\V1\User;

use App\Http\Controllers\Controller;
use App\Models\ServerAnytls;
use App\Models\ServerHysteria;
use App\Models\ServerShadowsocks;
use App\Models\ServerTrojan;
use App\Models\ServerTuic;
use App\Models\ServerV2node;
use App\Models\ServerVless;
use App\Models\ServerVmess;
use App\Models\StatUser;
use App\Models\StatUserServer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class StatController extends Controller
{
    public function getTrafficLog(Request $request)
    {
        $startAt = strtotime(date('Y-m-1'));
        if (Schema::hasTable('v2_stat_user_server')) {
            $records = StatUserServer::where('user_id', $request->user['id'])
                ->where('record_at', '>=', $startAt)
                ->orderBy('record_at', 'DESC')
                ->orderBy('id', 'DESC')
                ->get();

            if ($records->isNotEmpty()) {
                $this->fillServerStatRecords($records);
                $legacyRecords = $this->getLegacyTrafficRecords($request->user['id'], $startAt);
                $records = $records->concat($legacyRecords)
                    ->sortByDesc('record_at')
                    ->values();
                return response([
                    'data' => $records
                ]);
            }
        }

        $records = $this->getLegacyTrafficRecords($request->user['id'], $startAt);
        return response([
            'data' => $records
        ]);
    }

    private function getLegacyTrafficRecords($userId, $startAt)
    {
        $builder = StatUser::select([
            'u',
            'd',
            'record_at',
            'user_id',
            'server_rate'
        ])
            ->where('user_id', $userId)
            ->where('record_at', '>=', $startAt)
            ->orderBy('record_at', 'DESC');
        $records = $builder->get();
        foreach ($records as $record) {
            $record['server_name'] = '汇总';
            $record['server_type'] = '-';
            $record['total'] = $record['u'] + $record['d'];
            $record['total_with_rate'] = ($record['u'] + $record['d']) * $record['server_rate'];
        }
        return $records;
    }

    private function fillServerStatRecords($records): void
    {
        $serverNames = $this->getServerNames($records);
        foreach ($records as $record) {
            $key = $record['server_type'] . ':' . $record['server_id'];
            $record['server_name'] = $serverNames[$key] ?? $record['server_type'] . '#' . $record['server_id'];
            $record['total'] = $record['u'] + $record['d'];
            $record['total_with_rate'] = ($record['u'] + $record['d']) * $record['server_rate'];
        }
    }

    private function getServerNames($records): array
    {
        $serverModels = [
            'shadowsocks' => ServerShadowsocks::class,
            'v2ray' => ServerVmess::class,
            'trojan' => ServerTrojan::class,
            'vmess' => ServerVmess::class,
            'vless' => ServerVless::class,
            'tuic' => ServerTuic::class,
            'hysteria' => ServerHysteria::class,
            'anytls' => ServerAnytls::class,
            'v2node' => ServerV2node::class
        ];
        $idsByType = [];
        foreach ($records as $record) {
            if (!isset($serverModels[$record['server_type']])) continue;
            $idsByType[$record['server_type']][] = $record['server_id'];
        }

        $names = [];
        foreach ($idsByType as $serverType => $ids) {
            $servers = $serverModels[$serverType]::whereIn('id', array_unique($ids))
                ->get(['id', 'name'])
                ->keyBy('id');
            foreach ($servers as $server) {
                $names[$serverType . ':' . $server['id']] = $server['name'];
            }
        }
        return $names;
    }
}
