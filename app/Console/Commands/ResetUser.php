<?php

namespace App\Console\Commands;

use App\Models\Plan;
use App\Utils\Helper;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ResetUser extends LocalizedCommand
{
    protected $builder;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reset:user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $descriptionKey = 'console.descriptions.reset_user';

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
        if (!$this->confirm(__('console.reset_user.confirmation'))) {
            return;
        }
        ini_set('memory_limit', -1);
        $users = User::all();
        foreach ($users as $user)
        {
            $user->token = Helper::guid();
            $user->uuid = Helper::guid(true);
            $user->save();
            $this->info(__('console.reset_user.completed', ['email' => $user->email]));
        }
    }
}
