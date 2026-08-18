<?php
// Xóa sạch dữ liệu Session
$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();

// Chuyển hướng về trang Đăng nhập
header('Location: ' . base_url('login'));
exit;
