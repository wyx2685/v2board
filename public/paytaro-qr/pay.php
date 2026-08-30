<?php
/**
 * Paytaro 弹窗显码页（无收银台模式）—— 面板域名下的支付页面
 *
 * 由 Paytaro 一键脚本放置到面板公开目录（如 public/paytaro-qr/pay.php）。
 * 作用：
 *   ?uuid=<订单UUID>                  输出支付页面（服务端向 Paytaro 取支付数据，页面内联渲染，不跳转 Paytaro）
 *   ?action=status&uuid=<订单UUID>    代理 Paytaro 轻量状态接口，供页面每 3 秒轮询
 * 只转发到固定的 Paytaro 上游、只允许上述两个动作，不含任何密钥，可安全放在公开目录。
 * Xboard 等无法执行 public/*.php 的部署，由插件路由 require 本文件（先 define('PAYTARO_QR_EMBED', true)）并调用下方函数。
 *
 * 文档：https://v3.paytaro.com/#/docs/install-script   客服：https://t.me/paytaro
 */

if (!defined('PAYTARO_QR_API')) {
    define('PAYTARO_QR_API', 'https://v3.paytaro.com');
}
if (!defined('PAYTARO_QR_ASSET_BASE')) {
    define('PAYTARO_QR_ASSET_BASE', '/paytaro-qr'); // widget.js 所在的公开路径
}
if (!defined('PAYTARO_QR_VERSION')) {
    define('PAYTARO_QR_VERSION', '1.0.0');
}

if (!function_exists('paytaro_qr_valid_uuid')) {

function paytaro_qr_valid_uuid($uuid)
{
    return is_string($uuid) && preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $uuid) === 1;
}

/** 服务端 GET Paytaro，返回 [httpCode, array|null] */
function paytaro_qr_fetch($path)
{
    $url = PAYTARO_QR_API . $path;
    $body = false;
    $code = 0;
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => ['Accept: application/json', 'User-Agent: PaytaroQR/' . PAYTARO_QR_VERSION],
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    } else {
        $ctx = stream_context_create(['http' => ['method' => 'GET', 'timeout' => 15, 'header' => "Accept: application/json\r\n", 'ignore_errors' => true]]);
        $body = @file_get_contents($url, false, $ctx);
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
            $code = (int) $m[1];
        }
    }
    if ($body === false || $body === '') {
        return [$code ?: 0, null];
    }
    $data = json_decode($body, true);
    return [$code, is_array($data) ? $data : null];
}

/** 状态代理：返回 [httpCode, jsonString]（不直接输出，便于框架内复用） */
function paytaro_qr_status_response($uuid)
{
    if (!paytaro_qr_valid_uuid($uuid)) {
        return [400, json_encode(['error' => 'invalid uuid'])];
    }
    list($code, $data) = paytaro_qr_fetch('/v1/invoice/' . $uuid . '/status');
    if ($data === null) {
        return [502, json_encode(['error' => 'upstream unavailable', 'code' => $code])];
    }
    $out = [
        'status' => isset($data['status']) ? $data['status'] : null,
        'return_url' => isset($data['return_url']) ? $data['return_url'] : null,
        'expired_at' => isset($data['expired_at']) ? $data['expired_at'] : null,
        'server_time' => isset($data['server_time']) ? $data['server_time'] : null,
    ];
    return [($code >= 200 && $code < 300) ? 200 : $code, json_encode($out)];
}

/** 支付页面：返回 [httpCode, html]；$statusUrl 为页面轮询用的状态地址（相对或绝对） */
function paytaro_qr_page_response($uuid, $statusUrl)
{
    $order = null;
    $payment = null;
    $error = '';
    $code = 200;
    if (!paytaro_qr_valid_uuid($uuid)) {
        $error = '订单参数无效';
        $code = 400;
    } else {
        list($upCode, $data) = paytaro_qr_fetch('/v1/invoice/' . $uuid);
        if ($data === null) {
            $error = '支付服务暂时不可用，请稍后重试（' . ($upCode ?: '网络') . '）';
            $code = 502;
        } elseif ($upCode === 404 || (isset($data['error']) && $data['error'])) {
            $error = '订单不存在或已关闭';
            $code = 404;
        } else {
            $order = [
                'uuid' => isset($data['uuid']) ? $data['uuid'] : $uuid,
                'status' => isset($data['status']) ? $data['status'] : 'UNPAID',
                'order_amount' => isset($data['order_amount']) ? $data['order_amount'] : null,
                'order_currency' => isset($data['order_currency']) ? $data['order_currency'] : 'CNY',
                'expired_at' => isset($data['expired_at']) ? $data['expired_at'] : null,
                'server_time' => isset($data['server_time']) ? $data['server_time'] : time(),
                'return_url' => isset($data['return_url']) ? $data['return_url'] : '',
            ];
            $payment = (isset($data['payment']) && is_array($data['payment'])) ? $data['payment'] : null;
            if ($payment === null && $order['status'] === 'UNPAID') {
                $error = '订单尚未选择支付方式，请返回重新下单';
            }
        }
    }
    $flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
    $cfg = json_encode(['order' => $order, 'payment' => $payment, 'error' => $error, 'statusUrl' => $statusUrl], $flags);
    $asset = htmlspecialchars(rtrim(PAYTARO_QR_ASSET_BASE, '/') . '/widget.js?v=' . PAYTARO_QR_VERSION, ENT_QUOTES);
    $title = $error ? '支付' : ((isset($payment['name']) ? $payment['name'] : '') . ' 付款');
    $html = '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1"><meta name="robots" content="noindex">'
        . '<title>' . htmlspecialchars($title, ENT_QUOTES) . '</title><style>html,body{margin:0;background:#f3f4f6}</style></head><body>'
        . '<div id="paytaro-qr"></div><script src="' . $asset . '"></script>'
        . '<script>(function(){var cfg=' . $cfg . ';var host=document.getElementById("paytaro-qr");'
        . 'if(!window.PaytaroQR){host.innerHTML="<p style=\'text-align:center;padding:40px;font-family:sans-serif\'>组件加载失败，请刷新重试</p>";return;}'
        . 'if(cfg.error){PaytaroQR.render(host,{mode:"page",order:cfg.order||{status:"ERROR"},payment:null,texts:{error:cfg.error}});return;}'
        . 'PaytaroQR.render(host,{mode:"page",order:cfg.order,payment:cfg.payment,status:{url:cfg.statusUrl,interval:3000}});})();</script>'
        . '</body></html>';
    return [$code, $html];
}

/** 直接输出版本（独立部署时由下方入口调用） */
function paytaro_qr_status($uuid)
{
    list($code, $body) = paytaro_qr_status_response($uuid);
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo $body;
}

function paytaro_qr_page($uuid, $statusUrl)
{
    list($code, $html) = paytaro_qr_page_response($uuid, $statusUrl);
    http_response_code($code);
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store');
    echo $html;
}

} // function_exists guard

if (!defined('PAYTARO_QR_EMBED')) {
    $uuid = isset($_GET['uuid']) ? (string) $_GET['uuid'] : '';
    $action = isset($_GET['action']) ? (string) $_GET['action'] : '';
    if ($action === 'status') {
        paytaro_qr_status($uuid);
    } elseif ($action === '' || $action === 'page') {
        $self = basename($_SERVER['SCRIPT_NAME'] ?? 'pay.php');
        paytaro_qr_page($uuid, $self . '?action=status&uuid=' . rawurlencode($uuid));
    } else {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'not found';
    }
}