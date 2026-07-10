<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class V2boardUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'v2board:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'v2board 更新';

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
        \Artisan::call('config:cache');
        DB::connection()->getPdo();
        $file = \File::get(base_path() . '/database/update.sql');
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
            if (!$item) continue;
            try {
                DB::select(DB::raw($item));
            } catch (\Exception $e) {
            }
        }
        $this->updateTlsPinningSchema();
        \Artisan::call('horizon:terminate');
        $this->info('更新完毕，队列服务已重启，你无需进行任何操作。');
    }

    /**
     * update.sql is historical and is intentionally re-run by this project.
     * Keep this migration idempotent so upgrades do not silently miss the new
     * TLS pinning columns after an earlier SQL statement already exists.
     */
    private function updateTlsPinningSchema(): void
    {
        $servers = [
            ['table' => 'v2_server_trojan', 'column' => 'allow_insecure', 'after' => 'server_port'],
            ['table' => 'v2_server_hysteria', 'column' => 'insecure', 'after' => null],
            ['table' => 'v2_server_tuic', 'column' => 'insecure', 'after' => null],
            ['table' => 'v2_server_anytls', 'column' => 'insecure', 'after' => null],
        ];

        foreach ($servers as $server) {
            if (!Schema::hasTable($server['table'])) {
                continue;
            }

            if (!Schema::hasColumn($server['table'], $server['column'])) {
                $after = $server['after'] ? " AFTER `{$server['after']}`" : '';
                DB::statement("ALTER TABLE `{$server['table']}` ADD `{$server['column']}` varchar(16) NOT NULL DEFAULT '0'{$after}");
            }
            DB::statement("ALTER TABLE `{$server['table']}` MODIFY `{$server['column']}` varchar(16) NOT NULL DEFAULT '0' COMMENT 'TLS verification mode: 0, pincert, or 1'");

            if (!Schema::hasColumn($server['table'], 'pinned_peer_cert_sha256')) {
                DB::statement("ALTER TABLE `{$server['table']}` ADD `pinned_peer_cert_sha256` varchar(95) NULL AFTER `{$server['column']}`");
            }
            if (!Schema::hasColumn($server['table'], 'certificate_public_key_sha256')) {
                DB::statement("ALTER TABLE `{$server['table']}` ADD `certificate_public_key_sha256` varchar(128) NULL AFTER `pinned_peer_cert_sha256`");
            }
        }
    }
}
