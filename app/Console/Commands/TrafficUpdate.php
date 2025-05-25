<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class TrafficUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'traffic:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '流量更新任务';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        ini_set('memory_limit', -1);
        $uploads = Redis::hgetall('v2board_upload_traffic');
        Redis::del('v2board_upload_traffic');
        $downloads = Redis::hgetall('v2board_download_traffic');
        Redis::del('v2board_download_traffic');
        if (empty($uploads) && empty($downloads)) {
            return;
        }

        $users = User::whereIn('id', array_keys($downloads))->get();
        $time = time();
        try {
            DB::beginTransaction();
            foreach ($users as $user) {
                $user->update([
                    't' => $time,
                    'u' => $user->u + $uploads[$user->id],
                    'd' => $user->d + $downloads[$user->id],
                ]);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('流量更新失败: ' . $e->getMessage());
            return;
        }
    }
}
