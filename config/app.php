<?php
define('APP_TIMEZONE', $_ENV['APP_TIMEZONE'] ?? 'Asia/Taipei');
date_default_timezone_set(APP_TIMEZONE);

function env_bool(string $key, bool $default = false): bool
{
    $value = $_ENV[$key] ?? null;
    if ($value === null || $value === '') {
        return $default;
    }

    return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $default;
}

define('APP_NAME', $_ENV['APP_NAME'] ?? '會員管理系統');
define('APP_URL',  rtrim($_ENV['APP_URL']  ?? 'https://www.newpay.com.tw/member', '/'));
define('APP_ENV',  $_ENV['APP_ENV']  ?? 'development');
define('ADMIN_EMAIL', $_ENV['ADMIN_EMAIL'] ?? 'admin@system.com');
define('ADMIN_PASSWORD', $_ENV['ADMIN_PASSWORD'] ?? 'admin12345');
define('ADMIN_SESSION_TIMEOUT_SECONDS', (int) ($_ENV['ADMIN_SESSION_TIMEOUT_SECONDS'] ?? 1800));
define('MEMBER_SESSION_TIMEOUT_SECONDS', (int) ($_ENV['MEMBER_SESSION_TIMEOUT_SECONDS'] ?? 600));
define('MAIL_FROM', $_ENV['MAIL_FROM'] ?? 'martellwang@gmail.com');
define('MAIL_FROM_NAME', $_ENV['MAIL_FROM_NAME'] ?? 'NewPay');
define('MAIL_HOST', $_ENV['MAIL_HOST'] ?? '');
define('MAIL_PORT', (int) ($_ENV['MAIL_PORT'] ?? 587));
define('MAIL_USERNAME', $_ENV['MAIL_USERNAME'] ?? '');
define('MAIL_PASSWORD', $_ENV['MAIL_PASSWORD'] ?? '');
define('MAIL_ENCRYPTION', $_ENV['MAIL_ENCRYPTION'] ?? 'tls');
define('MAIL_EHLO_DOMAIN', $_ENV['MAIL_EHLO_DOMAIN'] ?? (parse_url(APP_URL, PHP_URL_HOST) ?: 'localhost'));
define('MAIL_VERIFY_PEER', env_bool('MAIL_VERIFY_PEER', APP_ENV !== 'development'));
define('GOOGLE_CLIENT_ID', $_ENV['GOOGLE_CLIENT_ID'] ?? '');
define('GOOGLE_CLIENT_SECRET', $_ENV['GOOGLE_CLIENT_SECRET'] ?? '');
