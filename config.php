<?php
session_start();

define('DB_HOST', 'localhost');
define('DB_NAME', 'web_film_demo');
define('DB_USER', 'root');
define('DB_PASS', '');

/*
 * Thư mục project dưới Apache document root.
 * Không thêm dấu / ở cuối. Ví dụ: /WEB_PHIM
 */
define('BASE_URL', '/WEB_PHIM');

/*
 * TMDB credentials:
 * - TMDB_API_KEY: API Key v3 (chuỗi 32 ký tự), dùng cho dự án local hiện tại.
 * - TMDB_API_TOKEN: API Read Access Token v4 (JWT bắt đầu bằng eyJ...), tùy chọn.
 * Có thể ưu tiên biến môi trường khi triển khai production.
 */
define('TMDB_API_BASE_URL', 'https://api.themoviedb.org/3');
define('TMDB_API_KEY', getenv('TMDB_API_KEY') ?: 'c0aee88039b4135e1a038809f3b663ff');
define('TMDB_API_TOKEN', getenv('TMDB_API_TOKEN') ?: '');

// Cookie settings: 30 days
define('COOKIE_EXPIRY', time() + (86400 * 30));

// Helper tạo URL tuyệt đối. BASE_URL cần khớp tên thư mục trong htdocs.
function base_url($path = '') {
    return 'http://localhost' . BASE_URL . '/' . ltrim($path, '/');
}
?>