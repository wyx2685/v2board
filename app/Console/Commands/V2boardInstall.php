<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Encryption\Encrypter;
use App\Models\User;
use App\Utils\Helper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Env;

class V2boardInstall extends Command
{
    protected $signature = 'v2board:install';
    protected $description = 'v2board 安装';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        try {
            $isDocker = env('docker', false);
            $this->info("__     ______  ____                      _  ");
            $this->info("\ \   / /___ \| __ )  ___   __ _ _ __ __| | ");
            $this->info(" \ \ / /  __) |  _ \ / _ \ / _` | '__/ _` | ");
            $this->info("  \ V /  / __/| |_) | (_) | (_| | | | (_| | ");
            $this->info("   \_/  |_____|____/ \___/ \__,_|_|  \__,_| ");

            if (
                (\File::exists(base_path() . '/.env') && $this->getEnvValue('INSTALLED'))
                || (env('INSTALLED', false) && $isDocker)
            ) {
                $securePath = admin_setting('secure_path', admin_setting('frontend_admin_path', hash('crc32b', config('app.key'))));
                $this->info("访问 http(s)://你的站点/{$securePath} 进入管理面板，你可以在用户中心修改你的密码。");
                $this->warn("如需重新安装请清空目录下 .env 文件的内容（Docker安装方式不可以删除此文件）");
                $this->warn("快捷清空.env命令：");
                note('rm .env && touch .env');
                return;
            }

            if (is_dir(base_path() . '/.env')) {
                $this->error('😔：安装失败，Docker环境下安装请保留空的 .env 文件');
                return;
            }

            if (!copy(base_path() . '/.env.example', base_path() . '/.env')) {
                abort(500, '复制环境文件失败，请检查目录权限');
            }

            // 保存环境变量
            $this->saveToEnv([
                'APP_KEY' => 'base64:' . base64_encode(Encrypter::generateKey('AES-256-CBC')),
                'DB_HOST' => $this->ask('请输入数据库地址（默认:localhost）', 'localhost'),
                'DB_PORT' => $this->ask('请输入数据库端口（默认:3306）', '3306'),
                'DB_DATABASE' => $this->ask('请输入数据库名'),
                'DB_USERNAME' => $this->ask('请输入数据库用户名'),
                'DB_PASSWORD' => $this->ask('请输入数据库密码')
            ]);

            \Artisan::call('config:clear');
            \Artisan::call('config:cache');

            try {
                DB::connection()->getPdo();
            } catch (\Exception $e) {
                abort(500, '数据库连接失败');
            }

            $file = \File::get(base_path() . '/database/install.sql');
            if (!$file) {
                abort(500, '数据库文件不存在');
            }

            $sql = str_replace("\n", "", $file);
            $sql = preg_split("/;/", $sql);
            if (!is_array($sql)) {
                abort(500, '数据库文件格式有误');
            }

            $this->info('正在导入数据库请稍等...');
            foreach ($sql as $item) {
                try {
                    DB::select(DB::raw($item));
                } catch (\Exception $e) {
                    // 这里可以记录错误日志或输出错误信息
                }
            }
            $this->info('数据库导入完成');

            // Redis 配置检查和设置
            $isRedisValid = false;
            while (!$isRedisValid) {
                if ($isDocker == 'true' && $this->confirm('是否启用Docker内置的Redis?', true)) {
                    $this->saveToEnv([
                        'REDIS_HOST' => '/run/redis-socket/redis.sock',
                        'REDIS_PORT' => 0,
                        'REDIS_PASSWORD' => null
                    ]);
                } else {
                    $this->saveToEnv([
                        'REDIS_HOST' => $this->ask('请输入Redis地址', '127.0.0.1'),
                        'REDIS_PORT' => $this->ask('请输入Redis端口', '6379'),
                        'REDIS_PASSWORD' => $this->ask('请输入Redis密码(默认: null)', '')
                    ]);
                }

                try {
                    $redis = new \Illuminate\Redis\RedisManager(app(), 'phpredis', [
                        'client' => 'phpredis',
                        'default' => [
                            'host' => env('REDIS_HOST'),
                            'password' => env('REDIS_PASSWORD'),
                            'port' => env('REDIS_PORT'),
                            'database' => 0,
                        ],
                    ]);
                    $redis->ping();
                    $isRedisValid = true;
                } catch (\Exception $e) {
                    $this->error("Redis连接失败：" . $e->getMessage());
                }
            }

            // 创建管理员账号
            $email = '';
            while (!$email) {
                $email = $this->ask('请输入管理员邮箱?');
            }
            $password = Helper::guid(false);
            if (!$this->registerAdmin($email, $password)) {
                abort(500, '管理员账号注册失败，请重试');
            }

            $this->info('🎉：一切就绪');
            $this->info("管理员邮箱：{$email}");
            $this->info("管理员密码：{$password}");

            $defaultSecurePath = hash('crc32b', config('app.key'));
            $this->info("访问 http(s)://你的站点/{$defaultSecurePath} 进入管理面板，你可以在用户中心修改你的密码。");

        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    private function registerAdmin($email, $password)
    {
        $user = new User();
        $user->email = $email;
        if (strlen($password) < 8) {
            abort(500, '管理员密码长度最小为8位字符');
        }
        $user->password = password_hash($password, PASSWORD_DEFAULT);
        $user->uuid = Helper::guid(true);
        $user->token = Helper::guid();
        $user->is_admin = 1;
        return $user->save();
    }

    private function saveToEnv($data = [])
    {
        foreach ($data as $key => $value) {
            $this->setEnvVar($key, $value);
        }
        return true;
    }

    private function setEnvVar($key, $value)
    {
        if (!is_bool(strpos($value, ' '))) {
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
        fclose($file);
    }

    private function getEnvValue($key, $default = null)
    {
        $dotenv = \Dotenv\Dotenv::createImmutable(base_path());
        $dotenv->load();

        return Env::get($key, $default);
    }
}
