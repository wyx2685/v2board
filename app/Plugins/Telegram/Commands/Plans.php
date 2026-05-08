<?php

namespace App\Plugins\Telegram\Commands;

use App\Models\Plan;
use App\Plugins\Telegram\Telegram;
use App\Utils\Helper;

class Plans extends Telegram {
    public $command = '/plans';
    public $description = 'Xem danh sách gói dịch vụ';
    public $callbackAction = ['plans', 'plan_detail'];

    private $periodLabels = [
        'month_price' => '1 Tháng',
        'quarter_price' => '3 Tháng',
        'half_year_price' => '6 Tháng',
        'year_price' => '1 Năm',
        'two_year_price' => '2 Năm',
        'three_year_price' => '3 Năm',
        'onetime_price' => 'Trọn Đời',
    ];

    public function handle($message, $match = []) {
        if (!$message->is_private) return;
        $this->showPlans($message->chat_id);
    }

    public function handleCallback($message, $params = []) {
        if (!$message->is_private) return;
        $action = $params[0] ?? '';

        switch ($action) {
            case 'plans':
                $this->showPlans($message->chat_id);
                break;
            case 'plan_detail':
                $planId = (int)($params[1] ?? 0);
                $this->showPlanDetail($message->chat_id, $planId);
                break;
        }
    }

    private function showPlans(int $chatId)
    {
        $plans = Plan::where('show', 1)->orderBy('sort', 'ASC')->get();

        if ($plans->isEmpty()) {
            $this->telegramService->sendMessage($chatId, '⚠️ Hiện tại không có gói dịch vụ nào.');
            return;
        }

        $text = "🛒 DANH SÁCH GÓI DỊCH VỤ\n";
        $text .= "━━━━━━━━━━━━━━━━━━━━━\n";
        $text .= "Chọn gói bạn muốn mua:\n";

        $keyboard = [];
        foreach ($plans as $plan) {
            $trafficText = Helper::trafficConvert($plan->transfer_enable * 1073741824);
            $minPrice = $this->getMinPrice($plan);

            $keyboard[] = [[
                'text' => "📦 {$plan->name} ({$trafficText}) - Từ {$minPrice}",
                'callback_data' => "plan_detail:{$plan->id}"
            ]];
        }

        $keyboard[] = [['text' => '⬅️ Quay Lại Menu', 'callback_data' => 'start']];
        $this->telegramService->sendMessageWithKeyboard($chatId, $text, $keyboard);
    }

    private function showPlanDetail(int $chatId, int $planId)
    {
        $plan = Plan::find($planId);
        if (!$plan || !$plan->show) {
            $this->telegramService->sendMessage($chatId, '⚠️ Gói dịch vụ không tồn tại.');
            return;
        }

        $trafficText = Helper::trafficConvert($plan->transfer_enable * 1073741824);

        $text = "📦 CHI TIẾT GÓI: {$plan->name}\n";
        $text .= "━━━━━━━━━━━━━━━━━━━━━\n";
        $text .= "📊 Dung lượng: {$trafficText}\n";

        if ($plan->device_limit) {
            $text .= "📱 Thiết bị tối đa: {$plan->device_limit}\n";
        }
        if ($plan->speed_limit) {
            $text .= "⚡ Tốc độ: {$plan->speed_limit} Mbps\n";
        }

        $text .= "━━━━━━━━━━━━━━━━━━━━━\n";
        $text .= "Chọn chu kỳ thanh toán:\n";

        $keyboard = [];
        foreach ($this->periodLabels as $period => $label) {
            if ($plan->$period !== null) {
                $price = number_format($plan->$period / 100, 0, ',', '.');
                $keyboard[] = [
                    ['text' => "💳 {$label} — {$price}đ", 'callback_data' => "buy:{$planId}:{$period}"]
                ];
            }
        }

        if ($plan->reset_price !== null) {
            $resetPrice = number_format($plan->reset_price / 100, 0, ',', '.');
            $keyboard[] = [
                ['text' => "🔄 Reset Data — {$resetPrice}đ", 'callback_data' => "buy:{$planId}:reset_price"]
            ];
        }

        $keyboard[] = [['text' => '⬅️ Quay Lại Danh Sách', 'callback_data' => 'plans']];
        $keyboard[] = [['text' => '🏠 Menu Chính', 'callback_data' => 'start']];

        $this->telegramService->sendMessageWithKeyboard($chatId, $text, $keyboard);
    }

    private function getMinPrice(Plan $plan): string
    {
        $prices = [];
        foreach ($this->periodLabels as $period => $label) {
            if ($plan->$period !== null && $plan->$period > 0) {
                $prices[] = $plan->$period;
            }
        }
        if (empty($prices)) return 'Miễn phí';
        return number_format(min($prices) / 100, 0, ',', '.') . 'đ';
    }
}
