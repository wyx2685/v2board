<?php
require_once __DIR__ . '/vendor/autoload.php';

use Adapterman\Adapterman;
use Workerman\Worker;
use Workerman\Timer;

putenv('APP_RUNNING_IN_CONSOLE=false');
define('MAX_REQUEST', 10000);
define('MAX_MEMORY_MB', 128);
define('isWEBMAN', true);

Adapterman::init();

class Logger {
    private static $c = ['INFO'=>"\033[32m",'WARN'=>"\033[33m",'ERROR'=>"\033[31m",'DEBUG'=>"\033[36m",'FATAL'=>"\033[35m"];
    public static function log($comp, $msg, $lvl = "INFO") {
        echo (self::$c[$lvl] ?? '') . "[" . date('Y-m-d H:i:s') . "] [{$comp}] [{$lvl}] {$msg}\033[0m\n";
    }
}

class CircuitBreaker {
    private $file, $state;
    
    public function __construct($dir) {
        $this->file = $dir . '/storage/.circuit-breaker';
        $this->load();
    }
    
    private function load() {
        $this->state = json_decode(@file_get_contents($this->file), true) ?: ['restarts'=>[],'open'=>false,'until'=>0];
        $this->state['restarts'] = array_filter($this->state['restarts'] ?? [], fn($t) => $t > time() - 300);
    }
    
    private function save() {
        @mkdir(dirname($this->file), 0755, true);
        file_put_contents($this->file, json_encode($this->state));
    }
    
    public function canRestart(): bool {
        $this->load();
        if ($this->state['open'] && time() < $this->state['until']) {
            Logger::log("CIRCUIT", "OPEN - cooldown " . ($this->state['until'] - time()) . "s", "FATAL");
            return false;
        }
        if ($this->state['open']) {
            $this->state = ['restarts'=>[],'open'=>false,'until'=>0];
            Logger::log("CIRCUIT", "CLOSED", "INFO");
        }
        if (count($this->state['restarts']) >= 5) {
            $this->state['open'] = true;
            $this->state['until'] = time() + 60;
            $this->save();
            Logger::log("CIRCUIT", "OPENED! Cooling 60s", "FATAL");
            return false;
        }
        return true;
    }
    
    public function record() {
        $this->state['restarts'][] = time();
        $this->save();
    }
}

class Manager {
    private $dir, $pid, $hash, $hashData, $workers, $hashes = [], $fails = 0, $cb;
    private $watchDirs = ['app','config','routes','database'];
    
    public function __construct($dir, $workers) {
        $this->dir = $dir;
        $this->workers = $workers;
        $this->pid = $dir . '/workerman.webman.php.pid';
        $this->hash = $dir . '/storage/.webman-hash';
        $this->hashData = $dir . '/storage/.webman-hash.data';
        $this->cb = new CircuitBreaker($dir);
        $this->hashes = @unserialize(@file_get_contents($this->hashData)) ?: [];
    }
    
    public function checkFiles() {
        $data = $this->scan();
        $h = md5(serialize($data));
        $lastH = @file_get_contents($this->hash);
        
        if (!$lastH) {
            $this->saveHash($h, $data);
            Logger::log("WATCHER", count($data) . " PHP files indexed", "INFO");
            return;
        }
        
        if ($h !== $lastH) {
            $changed = [];
            foreach ($data as $f => $m) {
                if (!isset($this->hashes[$f])) $changed[] = basename($f) . "(new)";
                elseif ($this->hashes[$f] !== $m) $changed[] = basename($f);
            }
            $this->saveHash($h, $data);
            $list = implode(', ', array_slice($changed, 0, 5));
            Logger::log("WATCHER", "Changed: {$list}" . (count($changed) > 5 ? " +" . (count($changed)-5) : ""), "INFO");
            $this->reload();
        }
    }
    
    private function scan(): array {
        $data = [];
        if (file_exists($this->dir . '/.env')) $data[$this->dir . '/.env'] = filemtime($this->dir . '/.env');
        foreach ($this->watchDirs as $d) {
            $p = $this->dir . '/' . $d;
            if (!is_dir($p)) continue;
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($p, RecursiveDirectoryIterator::SKIP_DOTS));
            foreach ($it as $f) if ($f->isFile() && $f->getExtension() === 'php') $data[$f->getPathname()] = $f->getMTime();
        }
        return $data;
    }
    
    private function saveHash($h, $data) {
        @mkdir(dirname($this->hash), 0755, true);
        file_put_contents($this->hash, $h);
        file_put_contents($this->hashData, serialize($data));
        $this->hashes = $data;
    }
    
    public function health() {
        $ok = true;
        $ch = curl_init('http://127.0.0.1:6600/api/v1/guest/comm/config');
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>5]);
        curl_exec($ch);
        if (!in_array(curl_getinfo($ch, CURLINFO_HTTP_CODE), [200,401,403,404])) $ok = false;
        curl_close($ch);
        
        try {
            $r = new Redis();
            if (!@$r->connect('127.0.0.1', 6379, 2) || !$r->ping()) $ok = false;
            $r->close();
        } catch (Exception $e) { $ok = false; }
        
        if ($ok) {
            $this->fails = 0;
            Logger::log("HEALTH", "OK", "DEBUG");
        } else {
            $this->fails++;
            Logger::log("HEALTH", "FAILED ({$this->fails}/3)", "ERROR");
            if ($this->fails >= 3) { $this->restart(); $this->fails = 0; }
        }
    }
    
    public function killZombies() {
        $master = $this->getMasterPid();
        if ($master <= 0) return;
        $out = shell_exec("pgrep -f 'webman|WorkerMan|AdapterMan' 2>/dev/null");
        $killed = 0;
        foreach (array_filter(explode("\n", trim($out ?: ''))) as $pid) {
            $pid = (int)$pid;
            if ($pid <= 0 || $pid === $master) continue;
            $ppid = (int)trim((string)shell_exec("ps -o ppid= -p $pid 2>/dev/null"));
            if ($ppid !== $master && $ppid !== 1) { posix_kill($pid, SIGKILL); $killed++; }
        }
        if ($killed) Logger::log("CLEANUP", "Killed {$killed} orphan(s)", "WARN");
    }
    
    public function checkWorkers() {
        $master = $this->getMasterPid();
        $count = $master > 0 ? count(array_filter(explode("\n", trim((string)shell_exec("pgrep -P {$master} 2>/dev/null") ?: '')))) : 0;
        
        if ($count < $this->workers - 1) {
            Logger::log("MONITOR", "Workers low: {$count}/{$this->workers}", "WARN");
            $this->reload();
        } elseif ($count > $this->workers + 2) {
            Logger::log("MONITOR", "Workers high: {$count}/{$this->workers}", "WARN");
            $this->killZombies();
        }
    }
    
    public function checkResources() {
        $load = sys_getloadavg()[0];
        $cpu = (int)shell_exec('nproc') ?: 4;
        if ($load > $cpu * 2) Logger::log("RESOURCE", "HIGH CPU: {$load}", "FATAL");
        
        $mem = explode(' ', trim((string)shell_exec("free -m | awk '/^Mem:/{print \$3, \$2}'")));
        $pct = round(($mem[0] / $mem[1]) * 100, 1);
        if ($pct > 90) Logger::log("RESOURCE", "CRITICAL RAM: {$pct}%", "FATAL");
    }
    
    private function getMasterPid(): int {
        return file_exists($this->pid) ? (int)trim((string)file_get_contents($this->pid)) : 0;
    }
    
    private function reload() {
        if (!$this->cb->canRestart()) return;
        $pid = $this->getMasterPid();
        if ($pid > 0) { posix_kill($pid, SIGUSR1); Logger::log("MANAGER", "Reload (PID: {$pid})", "INFO"); }
    }
    
    private function restart() {
        if (!$this->cb->canRestart()) return;
        $this->cb->record();
        $pid = $this->getMasterPid();
        if ($pid > 0) { posix_kill($pid, SIGUSR2); Logger::log("MANAGER", "Restart", "WARN"); }
    }
}

$workers = ((int)shell_exec('nproc') ?: 4) * 2;
$dir = __DIR__;

$http = new Worker('http://127.0.0.1:6600');
$http->count = $workers;
$http->name = 'AdapterMan';
$http->reloadable = true;

$http->onWorkerStart = function($w) use ($workers, $dir) {
    require $dir . '/start.php';
    if ($w->id !== 0) return;
    
    Logger::log("MANAGER", "════════════════════════════════════", "INFO");
    Logger::log("MANAGER", "WEBMAN STARTED | Workers: {$workers}", "INFO");
    Logger::log("MANAGER", "════════════════════════════════════", "INFO");
    
    $m = new Manager($dir, $workers);
    $m->killZombies();
    
    Timer::add(5, fn() => $m->checkFiles());
    Timer::add(15, fn() => $m->health());
    Timer::add(30, function() use ($m) { $m->killZombies(); $m->checkWorkers(); $m->checkResources(); });
};

$http->onMessage = function($conn, $req) {
    static $c = 0;
    $conn->send(run());
    if (memory_get_usage(true) > MAX_MEMORY_MB * 1024 * 1024 || ++$c > MAX_REQUEST) Worker::stopAll();
};

Worker::$pidFile = $dir . '/workerman.webman.php.pid';
Worker::$logFile = $dir . '/workerman.log';
Worker::runAll();
