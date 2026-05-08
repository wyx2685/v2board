<?php

namespace App\Plugins\Telegram\AutoOrder;

class VietQRHelper
{
    // ==========================================
    // CẤU HÌNH NGÂN HÀNG (Sửa trực tiếp tại đây)
    // ==========================================
    public const BANK_CODE = 'MB';                   // Mã ngân hàng (VD: MB, VCB, TCB, ACB, ...)
    public const BANK_ACCOUNT = '0123456789';        // Số tài khoản nhận tiền
    public const BANK_ACCOUNT_NAME = 'NGUYEN VAN A'; // Tên chủ tài khoản (viết HOA không dấu)
    public const TRANSFER_PREFIX = 'HD';             // Tiền tố nội dung chuyển khoản (VD: HD)

    /**
     * Generate the bank transfer description.
     * Format: {prefix}{order_id}, e.g. "HD1803"
     *
     * @param int $orderId
     * @return string
     */
    public static function getTransferContent(int $orderId): string
    {
        return self::TRANSFER_PREFIX . $orderId;
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
        if (empty(self::BANK_CODE) || empty(self::BANK_ACCOUNT)) {
            return null;
        }

        return sprintf(
            'https://img.vietqr.io/image/%s-%s-compact2.png?amount=%d&addInfo=%s&accountName=%s',
            self::BANK_CODE,
            self::BANK_ACCOUNT,
            $amountVND,
            urlencode($transferContent),
            urlencode(self::BANK_ACCOUNT_NAME)
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
        $formattedAmount = number_format($amountVND, 0, ',', '.');

        $text = "🏦 Ngân hàng: " . self::BANK_CODE . "\n";
        $text .= "👤 Chủ TK: " . self::BANK_ACCOUNT_NAME . "\n";
        $text .= "🔢 STK: " . self::BANK_ACCOUNT . "\n";
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
        return !empty(self::BANK_CODE) && !empty(self::BANK_ACCOUNT) && self::BANK_ACCOUNT !== '0123456789';
    }
}
