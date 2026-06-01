-- 为节点 tls_settings 写入证书 SHA256 指纹（Xray 26.5+ 兼容）
-- 使用前替换：节点ID、指纹、SNI域名

ALTER TABLE `v2_server_anytls`
  ADD COLUMN `pinned_peer_cert_sha256` varchar(128) DEFAULT NULL COMMENT 'TLS leaf cert SHA256 hex' AFTER `insecure`;

-- 示例：节点 id=1，指纹替换为实际值
-- UPDATE v2_server_anytls SET insecure=0, pinned_peer_cert_sha256='YOUR_HEX' WHERE id=1;

-- UPDATE v2_server_vless
-- SET tls_settings = JSON_SET(
--   COALESCE(tls_settings, '{}'),
--   '$.pinned_peer_cert_sha256', 'e8e2d387fdbffeb38e9c9065cf30a97ee23c0e3d32ee6f78ffae40966befccc9',
--   '$.allow_insecure', 0
-- )
-- WHERE id = 1;

-- VMess 节点示例
-- UPDATE v2_server_vmess SET tls_settings = JSON_SET(COALESCE(tls_settings, '{}'), '$.pinned_peer_cert_sha256', 'YOUR_SHA256_HEX', '$.allow_insecure', 0) WHERE id = 1;

-- Trojan 节点示例
-- UPDATE v2_server_trojan SET tls_settings = JSON_SET(COALESCE(tls_settings, '{}'), '$.pinned_peer_cert_sha256', 'YOUR_SHA256_HEX', '$.allow_insecure', 0) WHERE id = 1;

-- sing-box 自签证书额外可选字段（与 Xray pcs hex 不同）:
--   certificate_public_key_sha256: 公钥 SHA256 的 base64 数组
--   tls_certificate_pem: 自签证书 PEM 字符串（写入 sing-box tls.certificate）

-- 获取 sing-box 公钥 pin (base64):
-- echo | openssl s_client -connect 你的SNI:443 -servername 你的SNI 2>/dev/null \
--   | openssl x509 -pubkey -noout \
--   | openssl pkey -pubin -outform der \
--   | openssl dgst -sha256 -binary | openssl enc -base64

-- xray tls ping 你的SNI域名:443
-- 或:
-- echo | openssl s_client -connect 你的SNI域名:443 -servername 你的SNI域名 2>/dev/null | openssl x509 -noout -fingerprint -sha256 | awk -F= '{print $2}' | tr -d ':'
