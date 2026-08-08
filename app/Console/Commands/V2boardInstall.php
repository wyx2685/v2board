<?php

namespace App\Console\Commands;

use Illuminate\Encryption\Encrypter;
use App\Models\User;
use App\Utils\Helper;
use Illuminate\Support\Facades\DB;

class V2boardInstall extends LocalizedCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'v2board:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $descriptionKey = 'console.descriptions.install';

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
        try {
            $this->info("__     ______  ____                      _  ");
            $this->info("\ \   / /___ \| __ )  ___   __ _ _ __ __| | ");
            $this->info(" \ \ / /  __) |  _ \ / _ \ / _` | '__/ _` | ");
            $this->info("  \ V /  / __/| |_) | (_) | (_| | | | (_| | ");
            $this->info("   \_/  |_____|____/ \___/ \__,_|_|  \__,_| ");
            if (\File::exists(base_path() . '/.env')) {
                $securePath = config('v2board.secure_path', config('v2board.frontend_admin_path', hash('crc32b', config('app.key'))));
                $this->info(__('console.install.panel_url', ['path' => $securePath]));
                abort(500, __('console.install.remove_env_first'));
            }

            if (!copy(base_path() . '/.env.example', base_path() . '/.env')) {
                abort(500, __('console.install.copy_env_failed'));
            }
            $this->saveToEnv([
                'APP_KEY' => 'base64:' . base64_encode(Encrypter::generateKey('AES-256-CBC')),
                'DB_HOST' => $this->ask(__('console.install.database_host_prompt'), 'localhost'),
                'DB_DATABASE' => $this->ask(__('console.install.database_name_prompt')),
                'DB_USERNAME' => $this->ask(__('console.install.database_username_prompt')),
                'DB_PASSWORD' => $this->ask(__('console.install.database_password_prompt'))
            ]);
            \Artisan::call('config:clear');
            \Artisan::call('config:cache');
            try {
                DB::connection()->getPdo();
            } catch (\Exception $e) {
                abort(500, __('console.database.connection_failed'));
            }
            $file = \File::get(base_path() . '/database/install.sql');
            if (!$file) {
                abort(500, __('console.database.file_missing'));
            }
            $sql = str_replace("\n", "", $file);
            $sql = preg_split("/;/", $sql);
            if (!is_array($sql)) {
                abort(500, __('console.database.file_invalid'));
            }
            $this->info(__('console.database.importing'));
            foreach ($sql as $item) {
                try {
                    DB::select(DB::raw($item));
                } catch (\Exception $e) {
                }
            }
            $this->info(__('console.database.imported'));
            $email = '';
            while (!$email) {
                $email = $this->ask(__('console.install.admin_email_prompt'));
            }
            $password = Helper::guid(false);
            if (!$this->registerAdmin($email, $password)) {
                abort(500, __('console.install.admin_registration_failed'));
            }

            $this->info(__('console.install.ready'));
            $this->info(__('console.install.admin_email', ['email' => $email]));
            $this->info(__('console.install.admin_password', ['password' => $password]));

            $defaultSecurePath = hash('crc32b', config('app.key'));
            $this->info(__('console.install.panel_url', ['path' => $defaultSecurePath]));
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    private function registerAdmin($email, $password)
    {
        $user = new User();
        $user->email = $email;
        if (strlen($password) < 8) {
            abort(500, __('console.install.admin_password_too_short'));
        }
        $user->password = password_hash($password, PASSWORD_DEFAULT);
        $user->uuid = Helper::guid(true);
        $user->token = Helper::guid();
        $user->is_admin = 1;
        return $user->save();
    }

    private function saveToEnv($data = [])
    {
        function set_env_var($key, $value)
        {
            if (! is_bool(strpos($value, ' '))) {
                $value = '"' . $value . '"';
            }
            $key = strtoupper($key);

            $envPath = app()->environmentFilePath();
            $contents = file_get_contents($envPath);

            preg_match("/^{$key}=[^\r\n]*/m", $contents, $matches);

            $oldValue = count($matches) ? $matches[0] : '';

            if ($oldValue) {
                $contents = str_replace("{$oldValue}", "{$key}={$value}", $contents);
            } else {
                $contents = $contents . "\n{$key}={$value}\n";
            }

            $file = fopen($envPath, 'w');
            fwrite($file, $contents);
            return fclose($file);
        }
        foreach($data as $key => $value) {
            set_env_var($key, $value);
        }
        return true;
    }
}
