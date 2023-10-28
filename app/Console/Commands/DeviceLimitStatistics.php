<?php

namespace App\Console\Commands;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

use Illuminate\Console\Command;

class DeviceLimitStatistics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'device:statistics';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Thống kê số lượng thiết bị đang kết nối tới server';

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
     * @return int
     */
    public function handle()
    {

        $totaluser = User::all();
        foreach ($totaluser as $user) {
            if ($user->plan_id <= 0) {
                continue;
            }

            $countalive = 0;
            $ips_array = Cache::get('ALIVE_IP_USER_' . $user['id']);
            if ($ips_array) {
                $countalive = $ips_array['alive_ip'];
            }

            if ($user->device_limit > 0)
            {
                $user->device_count = $countalive;
                $user->save();
            }
        }
        return 0;
    }
}
