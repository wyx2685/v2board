<?php

namespace App\Plugins\Telegram\AutoOrder;

class VietQRHelper
{
    /**
     * Generate the bank transfer description.
     * Format: {prefix}{order_id}, e.g. "HD1803"
     *
     * @param int $orderId
     * @return string
     */
    public static function getTransferContent(int $orderId): string
    {
        $prefix = config('v2board.telegram_bank_transfer_prefix', 'HD');
        return $prefix . $orderId;
    }

    /**
     * Generate VietQR image URL for bank transfer.
     *
     * @param string $transferContent  Transfer description (e.g. "HD1803")
     * @param int    $amountVND        Amount in VND (already divided by 100 from cents)
     * @return string|null             URL of the QR image, or null if not configured
     */
    public static function generateQRUrl(string $transferContent, int $amountVND): ?string
    {
        $bankCode = config('v2board.telegram_bank_code', '');
        $bankAccount = config('v2board.telegram_bank_account', '');
        $bankAccountName = config('v2board.telegram_bank_account_name', '');

        if (empty($bankCode) || empty($bankAccount)) {
            return null;
        }

        return sprintf(
            'https://img.vietqr.io/image/%s-%s-compact2.png?amount=%d&addInfo=%s&accountName=%s',
            $bankCode,
            $bankAccount,
            $amountVND,
            urlencode($transferContent),
            urlencode($bankAccountName)
        );
    }

    /**
     * Get bank info text for display in Telegram caption.
     *
     * @param string $transferContent
     * @param int    $amountVND
     * @return string
     */
    public static function getBankInfoText(string $transferContent, int $amountVND): string
    {
        $bankCode = config('v2board.telegram_bank_code', '');
        $bankAccount = config('v2board.telegram_bank_account', '');
        $bankAccountName = config('v2board.telegram_bank_account_name', '');
        $formattedAmount = number_format($amountVND, 0, ',', '.');

        $text = "🏦 Ngân hàng: {$bankCode}\n";
        $text .= "👤 Chủ TK: {$bankAccountName}\n";
        $text .= "🔢 STK: {$bankAccount}\n";
        $text .= "📝 Nội dung CK: {$transferContent}\n";
        $text .= "💰 Số tiền: {$formattedAmount}đ";

        return $text;
    }

    /**
     * Check if bank config is properly set.
     *
     * @return bool
     */
    public static function isConfigured(): bool
    {
        return !empty(config('v2board.telegram_bank_code', ''))
            && !empty(config('v2board.telegram_bank_account', ''));
    }
}
