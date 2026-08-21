<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (GOOGLE_CLIENT_ID === '') {
    flash('Đăng nhập Google chưa được cấu hình. Xem hướng dẫn lấy GOOGLE_CLIENT_ID/SECRET trong config.php', 'error');
    header('Location: ' . base_url('login'));
    exit;
}

// state chống CSRF cho luồng OAuth
$state = bin2hex(random_bytes(16));
$_SESSION['google_oauth_state'] = $state;

$params = http_build_query([
    'client_id' => GOOGLE_CLIENT_ID,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'response_type' => 'code',
    'scope' => 'openid email profile',
    'state' => $state,
    'access_type' => 'online',
    'prompt' => 'select_account',
]);

header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . $params);
exit;
