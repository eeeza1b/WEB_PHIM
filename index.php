<?php
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

/*
 * .htaccess chuyển /browse thành index.php?page=browse.
 * Fallback $_GET giúp các URL cũ index.php?page=browse tiếp tục hoạt động.
 */
$allowed_pages = ['home', 'browse', 'movie', 'login', 'register', 'logout', 'profile', 'watchlist', 'mood'];
$page = strtolower((string) ($_GET['page'] ?? 'home'));
$page = preg_replace('/[^a-z0-9_-]/', '', $page);

if (!in_array($page, $allowed_pages, true)) {
    http_response_code(404);
    $page = 'home';
}

require_once 'includes/header.php';
require_once "views/{$page}.php";
require_once 'includes/footer.php';
?>