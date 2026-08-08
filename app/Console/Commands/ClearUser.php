<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use App\Models\User;

class ClearUser extends LocalizedCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clear:user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $descriptionKey = 'console.descriptions.clear_user';

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
        $builder = User::where('plan_id', NULL)
            ->where('transfer_enable', 0)
            ->where('expired_at', 0)
            ->where('last_login_at', NULL);
        $count = $builder->count();
        if ($builder->delete()) {
            $this->info(__('console.clear_user.completed', ['count' => $count]));
        }
    }
}
