<?php
namespace App\Services;

use App\Jobs\SendTelegramJob;
use App\Models\User;
use App\Utils\CacheKey;
use \Curl\Curl;
use Illuminate\Support\Facades\Cache;

class TelegramService {
    protected $api;

    public function __construct($token = '')
    {
        $this->api = 'https://api.telegram.org/bot' . config('v2board.telegram_bot_token', $token) . '/';
    }

    public function sendMessage(int $chatId, string $text, string $parseMode = '')
    {
        if ($parseMode === 'markdown') {
            $text = str_replace('_', '\_', $text);
        }
        $this->request('sendMessage', [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => $parseMode
        ]);
    }

    public function approveChatJoinRequest(int $chatId, int $userId)
    {
        $this->request('approveChatJoinRequest', [
            'chat_id' => $chatId,
            'user_id' => $userId
        ]);
    }

    public function declineChatJoinRequest(int $chatId, int $userId)
    {
        $this->request('declineChatJoinRequest', [
            'chat_id' => $chatId,
            'user_id' => $userId
        ]);
    }

    public function getMe()
    {
        return $this->request('getMe');
    }

    public function setWebhook(string $url)
    {
        $commands = $this->discoverCommands(base_path('app/Plugins/Telegram/Commands'));
        $this->setMyCommands($commands);
        return $this->request('setWebhook', [
            'url' => $url
        ]);
    }

    public function discoverCommands(string $directory): array
    {
        $commands = [];

        foreach (glob($directory . '/*.php') as $file) {
            $className = 'App\\Plugins\\Telegram\\Commands\\' . basename($file, '.php');

            if (!class_exists($className)) {
                require_once $file;
            }

            if (!class_exists($className)) {
                continue;
            }

            try {
                $ref = new \ReflectionClass($className);

                if (
                    $ref->hasProperty('command') &&
                    $ref->hasProperty('description')
                ) {
                    $commandProp = $ref->getProperty('command');
                    $descProp = $ref->getProperty('description');

                    $command = $commandProp->isStatic()
                        ? $commandProp->getValue()
                        : $ref->newInstanceWithoutConstructor()->command;

                    $description = $descProp->isStatic()
                        ? $descProp->getValue()
                        : $ref->newInstanceWithoutConstructor()->description;

                    $commands[] = [
                        'command' => $command,
                        'description' => __($description),
                    ];
                }
            } catch (\ReflectionException $e) {
                continue;
            }
        }
        return $commands;
    }
    
    public function setMyCommands(array $commands)
    {
        $this->request('setMyCommands', [
            'commands' => json_encode($commands),
        ]);
    }

    private function request(string $method, array $params = [])
    {
        $curl = new Curl();
        $curl->get($this->api . $method . '?' . http_build_query($params));
        $response = $curl->response;
        $curl->close();
        if (!isset($response->ok)) abort(500, __('Request failed'));
        if (!$response->ok) {
            abort(500, __('Telegram error: :error', ['error' => $response->description]));
        }
        return $response;
    }

    public function sendMessageWithAdmin($message, $isStaff = false, string $parseMode = '')
    {
        if (!config('v2board.telegram_bot_enable', 0)) return;
        foreach ($this->notificationRecipients($isStaff) as $user) {
            SendTelegramJob::dispatch($user->telegram_id, $message, $parseMode);
        }
    }

    public function sendTranslatedMessageWithAdmin(
        string $key,
        array $replace = [],
        bool $isStaff = false,
        string $parseMode = ''
    ) {
        if (!config('v2board.telegram_bot_enable', 0)) return;
        foreach ($this->notificationRecipients($isStaff) as $user) {
            SendTelegramJob::dispatch(
                $user->telegram_id,
                trans($key, $replace, $this->localeFor($user)),
                $parseMode
            );
        }
    }

    private function notificationRecipients(bool $isStaff)
    {
        return User::where(function ($query) use ($isStaff) {
            $query->where('is_admin', 1);
            if ($isStaff) {
                $query->orWhere('is_staff', 1);
            }
        })
            ->where('telegram_id', '!=', NULL)
            ->get();
    }

    private function localeFor(User $user): string
    {
        $locale = Cache::get(CacheKey::get('USER_LOCALE', $user->id));
        $supported = array_keys((array) config('app.supported_locales', []));

        return is_string($locale) && in_array($locale, $supported, true)
            ? $locale
            : (string) config('app.default_locale', 'vi-VN');
    }
}
