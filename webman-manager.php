<?php
class CLI {
    private $dir, $pid, $circuit, $workers;
    
    public function __construct() {
        $this->dir = __DIR__;
        $this->pid = $this->dir . '/workerman.webman.php.pid';
        $this->circuit = $this->dir . '/storage/.circuit-breaker';
        $this->workers = ((int)shell_exec('nproc') ?: 4) * 2;
    }
    
    public function status() {
        $pid = $this->getPid();
        $run = $pid > 0 && posix_kill($pid, 0);
        $w = $pid > 0 ? count(array_filter(explode("\n", trim(shell_exec("pgrep -P {$pid} 2>/dev/null") ?: '')))) : 0;
        $cb = json_decode(@file_get_contents($this->circuit), true) ?: [];
        $restarts = count(array_filter($cb['restarts'] ?? [], fn($t) => $t > time() - 300));
        
        echo "\n\033[36m╔═══════════════════════════════════════════╗\033[0m\n";
        echo "\033[36m║           WEBMAN STATUS                   ║\033[0m\n";
        echo "\033[36m╠═══════════════════════════════════════════╣\033[0m\n";
        printf("\033[36m║\033[0m  PID: %-36s\033[36m║\033[0m\n", $pid ?: 'N/A');
        printf("\033[36m║\033[0m  Status: %s%-28s\033[0m\033[36m║\033[0m\n", $run ? "\033[32m" : "\033[31m", $run ? 'RUNNING ✓' : 'STOPPED ✗');
        printf("\033[36m║\033[0m  Workers: %-31s\033[36m║\033[0m\n", "{$w} / {$this->workers}");
        printf("\033[36m║\033[0m  Circuit: %s%-28s\033[0m\033[36m║\033[0m\n", ($cb['open'] ?? false) ? "\033[31m" : "\033[32m", ($cb['open'] ?? false) ? 'OPEN' : 'CLOSED');
        printf("\033[36m║\033[0m  Restarts: %-30s\033[36m║\033[0m\n", "{$restarts}/5 (5min)");
        echo "\033[36m╚═══════════════════════════════════════════╝\033[0m\n\n";
    }
    
    public function start() {
        echo "\033[32m[START]\033[0m Starting...\n";
        shell_exec("pkill -9 -f 'WorkerMan' 2>/dev/null");
        sleep(2);
        shell_exec("cd {$this->dir} && php -c cli-php.ini webman.php start -d 2>&1");
        sleep(3);
        echo $this->getPid() > 0 ? "\033[32m[OK]\033[0m Started\n" : "\033[31m[FAIL]\033[0m\n";
    }
    
    public function stop() {
        echo "\033[33m[STOP]\033[0m Stopping...\n";
        shell_exec("pkill -f 'WorkerMan' 2>/dev/null");
        sleep(2);
        echo "\033[32m[OK]\033[0m Stopped\n";
    }
    
    public function restart() { $this->stop(); sleep(1); $this->start(); }
    
    public function reload() {
        $pid = $this->getPid();
        if ($pid > 0) { posix_kill($pid, SIGUSR1); echo "\033[32m[OK]\033[0m Reload sent\n"; }
        else echo "\033[31m[FAIL]\033[0m Not running\n";
    }
    
    public function reset() {
        @unlink($this->dir . '/storage/.webman-hash');
        @unlink($this->dir . '/storage/.webman-hash.data');
        @unlink($this->circuit);
        echo "\033[32m[OK]\033[0m Reset complete\n";
    }
    
    private function getPid(): int { return file_exists($this->pid) ? (int)trim(file_get_contents($this->pid)) : 0; }
    
    public function help() {
        echo "\nUsage: php webman-manager.php {status|start|stop|restart|reload|reset}\n\n";
    }
}

$cli = new CLI();
match($argv[1] ?? 'help') {
    'status' => $cli->status(),
    'start' => $cli->start(),
    'stop' => $cli->stop(),
    'restart' => $cli->restart(),
    'reload' => $cli->reload(),
    'reset' => $cli->reset(),
    default => $cli->help(),
};
