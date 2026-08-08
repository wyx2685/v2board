<?php

namespace App\Console\Commands;

use App\Jobs\OrderHandleJob;
use App\Models\Order;

class CheckOrder extends LocalizedCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:order';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $descriptionKey = 'console.descriptions.check_order';

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
        $orders = Order::whereIn('status', [0, 1])
            ->orderBy('created_at', 'ASC')
            ->get();
        foreach ($orders as $order) {
            OrderHandleJob::dispatch($order->trade_no);
        }
    }
}
