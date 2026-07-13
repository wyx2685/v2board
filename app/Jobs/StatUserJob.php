<?php

namespace App\Jobs;

use App\Models\StatUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StatUserJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $data;
    protected $server;
    protected $protocol;
    protected $recordType;

    public $tries = 3;
    public $timeout = 60;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $data, array $server, $protocol, $recordType = 'd')
    {
        $this->onQueue('stat');
        $this->data =$data;
        $this->server = $server;
        $this->protocol = $protocol;
        $this->recordType = $recordType;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $recordAt = strtotime(date('Y-m-d'));
        if ($this->recordType === 'm') {
            //
        }
        $attempt = 0;
        $maxAttempts = 3;
        $existingData = StatUser::where('record_at', $recordAt)
        ->where('server_rate', $this->server['rate'])
        ->whereIn('user_id', array_keys($this->data))
        ->select(['user_id', 'id', 'u', 'd'])
        ->get()
        ->keyBy('user_id');

        $hasUserServerStatTable = Schema::hasTable('v2_stat_user_server');
        while ($attempt < $maxAttempts) {
            $insertData = [];
            $insertServerData = [];
            try {
                DB::beginTransaction();
                $now = time();
                foreach($this->data as $userId => $trafficData){
                    if (isset($existingData[$userId])) {
                        $userdata = StatUser::where('id', $existingData[$userId]['id'])->first();
                        $userdata->update([
                            'u' => $userdata['u'] + $trafficData[0],
                            'd' => $userdata['d'] + $trafficData[1]
                        ]);
                    } else {
                        $insertData[] = [
                            'user_id' => $userId,
                            'server_rate' => $this->server['rate'],
                            'u' => $trafficData[0],
                            'd' => $trafficData[1],
                            'record_type' => $this->recordType,
                            'record_at' => $recordAt
                        ];
                    }
                    if ($hasUserServerStatTable) {
                        $insertServerData[] = [
                            'user_id' => $userId,
                            'server_id' => $this->server['id'],
                            'server_type' => $this->protocol,
                            'server_rate' => $this->server['rate'],
                            'u' => $trafficData[0],
                            'd' => $trafficData[1],
                            'record_type' => $this->recordType,
                            'record_at' => $recordAt,
                            'created_at' => $now,
                            'updated_at' => $now
                        ];
                    }
                }
                if (!empty($insertData)) {
                    collect($insertData)->chunk(500)->each(function ($chunk) {
                        StatUser::upsert($chunk->toArray(), ['user_id', 'server_rate', 'record_at']);
                    });
                }
                if (!empty($insertServerData)) {
                    collect($insertServerData)->chunk(500)->each(function ($chunk) {
                        DB::table('v2_stat_user_server')->upsert(
                            $chunk->toArray(),
                            ['user_id', 'server_id', 'server_type', 'record_at'],
                            [
                                'server_rate' => DB::raw('VALUES(`server_rate`)'),
                                'u' => DB::raw('`u` + VALUES(`u`)'),
                                'd' => DB::raw('`d` + VALUES(`d`)'),
                                'updated_at' => DB::raw('VALUES(`updated_at`)')
                            ]
                        );
                    });
                }
                DB::commit();
                return;
            } catch (\Exception $e) {
                DB::rollback();
                if (strpos($e->getMessage(), '40001') !== false || strpos(strtolower($e->getMessage()), 'deadlock') !== false) {
                    $attempt++;
                    if ($attempt < $maxAttempts) {
                        sleep(pow(2, $attempt));
                        continue;
                    }
                }
                abort(500, '用户统计数据失败'. $e->getMessage());
            }
        }
    }
}
