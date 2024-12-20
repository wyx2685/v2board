<?php

namespace App\Jobs;

use App\Models\StatServer;
use App\Models\StatUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

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

        $insertData = [];
        while ($attempt < $maxAttempts) {
            try {
                DB::beginTransaction();
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
                }
                if (!empty($insertData)) {
                    collect($insertData)->chunk(500)->each(function ($chunk) {
                        StatUser::upsert($chunk->toArray(), ['user_id', 'server_rate', 'record_at']);
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
