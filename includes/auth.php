<?php
require_once __DIR__ . '/db.php';

function current_user() {
    return $_SESSION['user'] ?? null;
}

function login_user($user, bool $remember = false) {
    $_SESSION['user'] = $user;
    track_login_session((int) $user['id'], $remember);
}

/**
 * Tạo 1 bản ghi phiên đăng nhập trong bảng user_sessions + lưu token vào
 * cookie riêng (uh_session_token, độc lập với PHP session id) để có thể
 * liệt kê/thu hồi từng phiên theo thiết bị, kể cả sau khi đổi PHP session.
 *
 * $remember = true  -> cookie sống 30 ngày (dùng cho "Ghi nhớ tôi"), vẫn
 *                       đăng nhập được sau khi đóng trình duyệt.
 * $remember = false -> cookie phiên (session cookie), tự xoá khi đóng
 *                       trình duyệt, giống hành vi PHP session mặc định.
 */
function track_login_session(int $userId, bool $remember = false): void {
    $token = bin2hex(random_bytes(32));
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 250);

    db_execute(
        'INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent) VALUES (?, ?, ?, ?)',
        [$userId, $token, $ip, $userAgent]
    );

    $cookieExpiry = $remember ? time() + 86400 * 30 : 0; // 0 = cookie phiên, mất khi đóng trình duyệt
    setcookie('uh_session_token', $token, $cookieExpiry, '/');
    $_SESSION['uh_session_token'] = $token;
}

/**
 * Tự đăng nhập lại bằng cookie uh_session_token khi PHP session đã mất
 * (vd: vừa đóng và mở lại trình duyệt) nhưng người dùng đã tick "Ghi nhớ tôi"
 * ở lần đăng nhập trước, nên cookie 30 ngày vẫn còn sống.
 * Gọi hàm này ở đầu mỗi request, TRƯỚC khi kiểm tra current_user().
 */
function attempt_auto_login(): void {
    if (current_user() || empty($_COOKIE['uh_session_token'])) {
        return;
    }

    $token = $_COOKIE['uh_session_token'];
    $row = db_select_one(
        'SELECT u.* FROM user_sessions s JOIN users u ON u.id = s.user_id WHERE s.session_token = ?',
        [$token]
    );

    if (!$row || !empty($row['is_banned'])) {
        setcookie('uh_session_token', '', time() - 3600, '/');
        return;
    }

    unset($row['password']);
    $_SESSION['user'] = $row;
    $_SESSION['uh_session_token'] = $token;
    db_execute('UPDATE user_sessions SET last_activity_at = NOW() WHERE session_token = ?', [$token]);
}

/** Cập nhật "hoạt động gần nhất" cho phiên hiện tại (gọi ở đầu mỗi request khi đã đăng nhập). */
function touch_current_session(): void {
    $token = $_SESSION['uh_session_token'] ?? ($_COOKIE['uh_session_token'] ?? null);
    if ($token) {
        db_execute('UPDATE user_sessions SET last_activity_at = NOW() WHERE session_token = ?', [$token]);
    }
}

function current_session_token(): ?string {
    return $_SESSION['uh_session_token'] ?? ($_COOKIE['uh_session_token'] ?? null);
}

function logout_user() {
    $token = current_session_token();
    if ($token) {
        db_execute('DELETE FROM user_sessions WHERE session_token = ?', [$token]);
    }
    setcookie('uh_session_token', '', time() - 3600, '/');
    session_destroy();
    header('Location: ' . base_url('index.php?page=login'));
    exit;
}

function require_login() {
    attempt_auto_login();
    if (!current_user()) {
        header('Location: ' . base_url('index.php?page=login'));
        exit;
    }
}

function require_admin() {
    require_login();
    if (current_user()['role'] !== 'admin') {
        header('Location: ' . base_url('index.php'));
        exit;
    }
}

function attempt_login($username, $password) {
    $user = db_select_one("SELECT * FROM users WHERE username = ?", [$username]);
    if ($user && !empty($user['password']) && password_verify($password, $user['password'])) {
        if ($user['is_banned']) return false;
        unset($user['password']);
        return $user;
    }
    return false;
}

function register_user($username, $email, $password) {
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    return db_insert("INSERT INTO users (username, email, password) VALUES (?, ?, ?)", [$username, $email, $hashed]);
}

function is_logged_in(): bool {
    return current_user() !== null;
}
