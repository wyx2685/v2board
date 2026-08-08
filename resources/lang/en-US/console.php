<?php

return [
    'descriptions' => [
        'reset_user' => 'Reset security information for all users',
        'check_ticket' => 'Check support ticket status',
        'install' => 'Install V2Board',
        'statistics' => 'Generate statistics',
        'check_server' => 'Check server status',
        'reset_traffic' => 'Reset used traffic',
        'send_remind_mail' => 'Send reminder emails',
        'reset_password' => 'Reset a user password',
        'traffic_update' => 'Update used traffic',
        'check_commission' => 'Process commissions',
        'check_order' => 'Check orders',
        'update' => 'Update V2Board',
        'reset_log' => 'Clear logs',
        'check_renewal' => 'Process automatic renewals',
        'clear_user' => 'Delete users with no data',
    ],
    'reset_user' => [
        'confirmation' => 'Are you sure you want to reset security information for all users?',
        'completed' => 'Reset security information for user :email.',
    ],
    'install' => [
        'panel_url' => 'Open the admin panel at http(s)://your-domain/:path. You can change your password in the user center.',
        'remove_env_first' => 'To reinstall, delete the .env file from the project directory.',
        'copy_env_failed' => 'Could not copy the environment file. Check the directory permissions.',
        'database_host_prompt' => 'Enter the database host (default: localhost)',
        'database_name_prompt' => 'Enter the database name',
        'database_username_prompt' => 'Enter the database username',
        'database_password_prompt' => 'Enter the database password',
        'admin_email_prompt' => 'Enter the administrator email address',
        'admin_registration_failed' => 'Could not create the administrator account. Try again.',
        'ready' => 'Installation completed.',
        'admin_email' => 'Administrator email: :email',
        'admin_password' => 'Administrator password: :password',
        'admin_password_too_short' => 'The administrator password must contain at least 8 characters.',
    ],
    'database' => [
        'connection_failed' => 'Could not connect to the database.',
        'file_missing' => 'The database file was not found.',
        'file_invalid' => 'The database file format is invalid.',
        'importing' => 'Importing the database. Please wait...',
        'imported' => 'Database import completed.',
    ],
    'check_server' => [
        'offline_notification' => "Server offline notification\n----\nServer name: :name\nServer address: :host\n",
    ],
    'reset_traffic' => [
        'failed' => 'Could not reset user traffic: :error',
        'failed_notification' => ':date Could not reset user traffic: :error',
    ],
    'reset_password' => [
        'email_not_found' => 'The email address was not found.',
        'failed' => 'Could not reset the password.',
        'completed' => 'Password reset successfully.',
        'new_password' => 'The new password is :password. Change it as soon as possible.',
    ],
    'update' => [
        'completed' => 'The update is complete and the queue service has restarted. No further action is required.',
    ],
    'clear_user' => [
        'completed' => 'Deleted users with no data: :count',
    ],
    'statistics' => [
        'completed' => 'Statistics completed in :seconds seconds.',
        'server_failed' => 'Could not save server statistics.',
        'user_failed' => 'Could not save user statistics.',
    ],
    'traffic_update' => [
        'failed' => 'Traffic update failed: :error',
    ],
    'renewal' => [
        'insufficient_balance' => 'The user balance is insufficient for automatic renewal.',
        'failed' => 'Automatic renewal failed.',
        'disable_failed' => 'Automatic renewal failed and could not be disabled for the user.',
    ],
];
