<?php

namespace App\Services;

use App\Utils\Helper;
use Illuminate\Database\Eloquent\Model;

class CertPinService
{
    /**
     * Persist leaf-cert SHA256 pin reported by node backend.
     */
    public function updateFromNodeReport(Model $server, string $nodeType, string $pin): bool
    {
        $pin = Helper::getTlsPinSha256(['pinned_peer_cert_sha256' => $pin]);
        if ($pin === '' || strlen($pin) !== 64) {
            return false;
        }

        switch ($nodeType) {
            case 'v2node':
                return $this->updateV2nodeTlsSettings($server, $pin);
            case 'anytls':
                return $this->updateAnytlsColumn($server, $pin);
            case 'vless':
            case 'vmess':
            case 'trojan':
            case 'tuic':
            case 'hysteria':
                return $this->updateTlsSettingsColumn($server, $pin);
            default:
                return false;
        }
    }

    private function updateV2nodeTlsSettings(Model $server, string $pin): bool
    {
        $tlsSettings = $server->tls_settings ?? [];
        if (!is_array($tlsSettings)) {
            $tlsSettings = [];
        }
        if (($tlsSettings['pinned_peer_cert_sha256'] ?? '') === $pin
            && (int)($tlsSettings['allow_insecure'] ?? 0) === 0) {
            return true;
        }
        $tlsSettings['pinned_peer_cert_sha256'] = $pin;
        $tlsSettings['allow_insecure'] = 0;
        $server->update(['tls_settings' => $tlsSettings]);
        return true;
    }

    private function updateAnytlsColumn(Model $server, string $pin): bool
    {
        if (($server->pinned_peer_cert_sha256 ?? '') === $pin && (int)($server->insecure ?? 1) === 0) {
            return true;
        }
        $server->update([
            'pinned_peer_cert_sha256' => $pin,
            'insecure' => 0,
        ]);
        return true;
    }

    private function updateTlsSettingsColumn(Model $server, string $pin): bool
    {
        $tlsSettings = $server->tls_settings ?? [];
        if (!is_array($tlsSettings)) {
            $tlsSettings = [];
        }
        if (($tlsSettings['pinned_peer_cert_sha256'] ?? '') === $pin
            && (int)($tlsSettings['allow_insecure'] ?? 0) === 0) {
            return true;
        }
        $tlsSettings['pinned_peer_cert_sha256'] = $pin;
        $tlsSettings['allow_insecure'] = 0;
        $server->update(['tls_settings' => $tlsSettings]);
        return true;
    }
}
