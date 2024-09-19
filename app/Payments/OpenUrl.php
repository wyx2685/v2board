<?php

namespace App\Payments;

class OpenUrl {
    public function __construct($config) {
        $this->config = $config;
    }

    public function form()
    {
        return [
            'open_url' => [
                'label' => '打开链接',
                'description' => '',
                'type' => 'input',
            ],
        ];
    }

    public function pay($order) {
        return [
            'type' => 1, // Redirect to url
            'data' => $this->config['open_url'],
        ];
        
    }

    public function notify($params) {

        return [
            'trade_no' => "123",
            'callback_no' => "123"
        ];
    }
    
}
