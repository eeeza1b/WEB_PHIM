<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

function google_oauth_fail(string $reason): void {
    flash('Đăng nhập Google thất bại: ' . $reason, 'error');
    header('Location: ' . base_url('login'));
    exit;
}

if (GOOGLE_CLIENT_ID === '' || GOOGLE_CLIENT_SECRET === '') {
    google_oauth_fail('chưa cấu hình GOOGLE_CLIENT_ID / GOOGLE_CLIENT_SECRET trong config.php');
}

$state = $_GET['state'] ?? '';
if (!$state || $state !== ($_SESSION['google_oauth_state'] ?? null)) {
    google_oauth_fail('state không hợp lệ (có thể do phiên hết hạn, hãy thử đăng nhập lại)');
}
unset($_SESSION['google_oauth_state']);

if (!empty($_GET['error'])) {
    google_oauth_fail('bạn đã huỷ đăng nhập hoặc Google từ chối yêu cầu');
}

$code = $_GET['code'] ?? '';
if (!$code) {
    google_oauth_fail('không nhận được authorization code từ Google');
}

// 1) Đổi authorization code lấy access token
$tokenResponse = google_http_post('https://oauth2.googleapis.com/token', [
    'code' => $code,
    'client_id' => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'grant_type' => 'authorization_code',
]);

$tokenData = json_decode((string) $tokenResponse, true);
if (empty($tokenData['access_token'])) {
    google_oauth_fail('không lấy được access token (kiểm tra lại Client ID/Secret và Redirect URI trên Google Cloud Console)');
}

// 2) Dùng access token lấy thông tin người dùng
$ch = curl_init('https://www.googleapis.com/oauth2/v3/userinfo');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $tokenData['access_token']]);
$userInfoResponse = curl_exec($ch);
curl_close($ch);

$googleUser = json_decode((string) $userInfoResponse, true);
if (empty($googleUser['sub']) || empty($googleUser['email'])) {
    google_oauth_fail('không lấy được thông tin tài khoản Google');
}

$googleId = (string) $googleUser['sub'];
$email = (string) $googleUser['email'];
$name = (string) ($googleUser['name'] ?? explode('@', $email)[0]);
$picture = (string) ($googleUser['picture'] ?? '');

// 3) Tìm user đã liên kết Google trước đó, hoặc user có cùng email, hoặc tạo mới
$user = db_select_one('SELECT * FROM users WHERE google_id = ?', [$googleId]);

if (!$user) {
    $user = db_select_one('SELECT * FROM users WHERE email = ?', [$email]);
    if ($user) {
        db_execute('UPDATE users SET google_id = ?, avatar_url = COALESCE(avatar_url, ?) WHERE id = ?', [$googleId, $picture, $user['id']]);
    }
}

if (!$user) {
    // Tạo username duy nhất từ email (vd: "an.nguyen" -> "an_nguyen"), thêm số nếu trùng.
    $baseUsername = preg_replace('/[^a-z0-9_]/', '_', strtolower(explode('@', $email)[0]));
    $username = $baseUsername;
    $suffix = 1;
    while (db_select_one('SELECT id FROM users WHERE username = ?', [$username])) {
        $username = $baseUsername . $suffix;
        $suffix++;
    }

    $newId = db_insert(
        'INSERT INTO users (username, email, password, role, is_banned, google_id, avatar_url, created_at) VALUES (?, ?, NULL, ?, 0, ?, ?, NOW())',
        [$username, $email, 'user', $googleId, $picture]
    );
    $user = db_select_one('SELECT * FROM users WHERE id = ?', [$newId]);
}

if (!empty($user['is_banned'])) {
    google_oauth_fail('tài khoản này đã bị khoá');
}

unset($user['password']);
login_user($user, true); // Đăng nhập Google mặc định "ghi nhớ" 30 ngày, giống hành vi thông thường của các web khác.

header('Location: ' . base_url('index.php'));
exit;

/** Gửi POST request dạng form-urlencoded bằng cURL (không cần cài thư viện ngoài). */
function google_http_post(string $url, array $fields): string {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    $response = curl_exec($ch);
    curl_close($ch);
    return (string) $response;
}
