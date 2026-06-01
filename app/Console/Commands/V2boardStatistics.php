<?php

namespace App\Console\Commands;

use App\Models\StatServer;
use App\Models\StatUser;
use App\Models\StatUserServer;
use App\Services\StatisticalService;
use Illuminate\Console\Command;
use App\Models\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class V2boardStatistics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'v2board:statistics';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '统计任务';

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
        $startAt = microtime(true);
        ini_set('memory_limit', -1);
        //$this->statUser();
        //$this->statServer();
        $this->syncStatUserSummaryFromServerStats();
        $this->stat();
        info('统计任务执行完毕。耗时:' . (microtime(true) - $startAt) / 1000);
    }

    private function statServer()
    {
        try {
            DB::beginTransaction();
            $createdAt = time();
            $recordAt = strtotime('-1 day', strtotime(date('Y-m-d')));
            $statService = new StatisticalService();
            $statService->setStartAt($recordAt);
            $statService->setServerStats();
            $stats = $statService->getStatServer();
            foreach ($stats as $stat) {
                if (!StatServer::insert([
                    'server_id' => $stat['server_id'],
                    'server_type' => $stat['server_type'],
                    'u' => $stat['u'],
                    'd' => $stat['d'],
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                    'record_type' => 'd',
                    'record_at' => $recordAt
                ])) {
                    throw new \Exception('stat server fail');
                }
            }
            DB::commit();
            $statService->clearStatServer();
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error($e->getMessage(), ['exception' => $e]);
        }
    }

    private function statUser()
    {
        try {
            DB::beginTransaction();
            $createdAt = time();
            $recordAt = strtotime('-1 day', strtotime(date('Y-m-d')));
            $statService = new StatisticalService();
            $statService->setStartAt($recordAt);
            $statService->setUserStats();
            $stats = $statService->getStatUser();
            foreach ($stats as $stat) {
                if (!StatUser::insert([
                    'user_id' => $stat['user_id'],
                    'u' => $stat['u'],
                    'd' => $stat['d'],
                    'server_rate' => $stat['server_rate'],
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                    'record_type' => 'd',
                    'record_at' => $recordAt
                ])) {
                    throw new \Exception('stat user fail');
                }
            }
            DB::commit();
            $statService->clearStatUser();
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error($e->getMessage(), ['exception' => $e]);
        }
    }

    private function stat()
    {
        try {
            $endAt = strtotime(date('Y-m-d'));
            $startAt = strtotime('-1 day', $endAt);
            $statisticalService = new StatisticalService();
            $statisticalService->setStartAt($startAt);
            $statisticalService->setEndAt($endAt);
            $data = $statisticalService->generateStatData();
            $data['record_at'] = $startAt;
            $data['record_type'] = 'd';
            $statistic = Stat::where('record_at', $startAt)
                ->where('record_type', 'd')
                ->first();
            if ($statistic) {
                $statistic->update($data);
                return;
            }
            Stat::create($data);
        } catch (\Exception $e) {
            \Log::error($e->getMessage(), ['exception' => $e]);
        }
    }

    private function syncStatUserSummaryFromServerStats()
    {
        if (!Schema::hasTable('v2_stat_user_server')) {
            return;
        }

        try {
            $recordAt = strtotime('-1 day', strtotime(date('Y-m-d')));
            $createdAt = time();
            $stats = StatUserServer::select([
                'user_id',
                'server_rate',
                'record_at',
                DB::raw('SUM(u) as u'),
                DB::raw('SUM(d) as d')
            ])
                ->where('record_at', $recordAt)
                ->where('record_type', 'd')
                ->groupBy('user_id', 'server_rate', 'record_at')
                ->get()
                ->map(function ($stat) use ($createdAt) {
                    return [
                        'user_id' => $stat['user_id'],
                        'server_rate' => $stat['server_rate'],
                        'u' => $stat['u'],
                        'd' => $stat['d'],
                        'record_type' => 'd',
                        'record_at' => $stat['record_at'],
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt
                    ];
                });

            if ($stats->isEmpty()) {
                return;
            }

            $stats->chunk(500)->each(function ($chunk) {
                StatUser::upsert(
                    $chunk->toArray(),
                    ['server_rate', 'user_id', 'record_at'],
                    ['u', 'd', 'record_type', 'updated_at']
                );
            });
        } catch (\Exception $e) {
            \Log::error($e->getMessage(), ['exception' => $e]);
        }
    }
}
