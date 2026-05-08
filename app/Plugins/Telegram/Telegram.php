<?php

namespace App\Plugins\Telegram;

use App\Services\TelegramService;

abstract class Telegram {
    abstract protected function handle($message, $match = []);
    public $telegramService;

    public function __construct()
    {
        $this->telegramService = new TelegramService();
    }

    /**
     * Override in subclass to handle inline keyboard callback queries.
     */
    public function handleCallback($message, $params = [])
    {
        // Default: do nothing. Override in subclass.
    }
}
