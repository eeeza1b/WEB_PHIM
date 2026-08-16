<?php
require_once __DIR__ . '/db.php';

function current_user() {
    return $_SESSION['user'] ?? null;
}

function login_user($user) {
    $_SESSION['user'] = $user;
}

function logout_user() {
    session_destroy();
    header('Location: ' . base_url('index.php?page=login'));
    exit;
}

function require_login() {
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
    if ($user && password_verify($password, $user['password'])) {
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
