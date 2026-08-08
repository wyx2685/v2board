<?php

return [
    'descriptions' => [
        'reset_user' => '重置所有用户的安全信息',
        'check_ticket' => '检查工单状态',
        'install' => '安装 V2Board',
        'statistics' => '生成统计数据',
        'check_server' => '检查服务器状态',
        'reset_traffic' => '重置用户流量',
        'send_remind_mail' => '发送提醒邮件',
        'reset_password' => '重置用户密码',
        'traffic_update' => '更新用户流量',
        'check_commission' => '处理返佣',
        'check_order' => '检查订单',
        'update' => '更新 V2Board',
        'reset_log' => '清理日志',
        'check_renewal' => '处理自动续费',
        'clear_user' => '删除无数据用户',
    ],
    'reset_user' => [
        'confirmation' => '确定要重置所有用户的安全信息吗？',
        'completed' => '已重置用户 :email 的安全信息。',
    ],
    'install' => [
        'panel_url' => '访问 http(s)://your-domain/:path 进入管理面板。你可以在用户中心修改密码。',
        'remove_env_first' => '如需重新安装，请删除项目目录中的 .env 文件。',
        'copy_env_failed' => '无法复制环境文件，请检查目录权限。',
        'database_host_prompt' => '请输入数据库地址（默认：localhost）',
        'database_name_prompt' => '请输入数据库名',
        'database_username_prompt' => '请输入数据库用户名',
        'database_password_prompt' => '请输入数据库密码',
        'admin_email_prompt' => '请输入管理员邮箱',
        'admin_registration_failed' => '管理员账号创建失败，请重试。',
        'ready' => '安装完成。',
        'admin_email' => '管理员邮箱：:email',
        'admin_password' => '管理员密码：:password',
        'admin_password_too_short' => '管理员密码至少需要 8 个字符。',
    ],
    'database' => [
        'connection_failed' => '数据库连接失败。',
        'file_missing' => '数据库文件不存在。',
        'file_invalid' => '数据库文件格式无效。',
        'importing' => '正在导入数据库，请稍候...',
        'imported' => '数据库导入完成。',
    ],
    'check_server' => [
        'offline_notification' => "服务器离线通知\n----\n服务器名称：:name\n服务器地址：:host\n",
    ],
    'reset_traffic' => [
        'failed' => '用户流量重置失败：:error',
        'failed_notification' => ':date 用户流量重置失败：:error',
    ],
    'reset_password' => [
        'email_not_found' => '邮箱不存在。',
        'failed' => '密码重置失败。',
        'completed' => '密码重置成功。',
        'new_password' => '新密码为 :password，请尽快修改密码。',
    ],
    'update' => [
        'completed' => '更新完成，队列服务已重启，无需进行其他操作。',
    ],
    'clear_user' => [
        'completed' => '已删除无数据用户：:count',
    ],
    'statistics' => [
        'completed' => '统计任务已完成，耗时 :seconds 秒。',
        'server_failed' => '服务器统计数据保存失败。',
        'user_failed' => '用户统计数据保存失败。',
    ],
    'traffic_update' => [
        'failed' => '流量更新失败：:error',
    ],
    'renewal' => [
        'insufficient_balance' => '用户余额不足，无法自动续费。',
        'failed' => '自动续费失败。',
        'disable_failed' => '自动续费失败，且无法为用户关闭自动续费。',
    ],
];
