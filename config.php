<?php
// Output buffering: cho phép gọi header()/setcookie() (vd: lưu cookie tâm trạng ở views/mood.php,
// hoặc redirect trong actions/*.php) dù includes/header.php đã in ra HTML trước đó trong cùng
// request, tránh lỗi "Cannot modify header information - headers already sent".
ob_start();

session_start();

define('DB_HOST', 'localhost');
define('DB_NAME', 'web_film_demo');
define('DB_USER', 'root');
define('DB_PASS', '');

/*
 * Thư mục project dưới Apache document root.
 * Không thêm dấu / ở cuối.
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

/*
 * Google OAuth 2.0 — lấy 2 giá trị này ở Google Cloud Console
 * (APIs & Services > Credentials > OAuth Client ID > Web application):
 *   - Authorized redirect URI cần khai báo đúng GOOGLE_REDIRECT_URI bên dưới,
 *     ví dụ: http://localhost/WEB_PHIM/actions/google_callback.php
 *
 * KHÔNG hardcode Client Secret thẳng vào file này (file này sẽ được đưa lên Git).
 * Thiết lập biến môi trường Windows TRƯỚC KHI chạy Apache/XAMPP:
 *   setx GOOGLE_CLIENT_ID "xxxxx.apps.googleusercontent.com"
 *   setx GOOGLE_CLIENT_SECRET "GOCSPX-xxxxxxxxxxxx"
 * Sau khi setx, PHẢI khởi động lại XAMPP/terminal để biến môi trường được nhận.
 *
 * Nếu để trống, nút "Đăng nhập Google" sẽ báo lỗi rõ ràng thay vì lỗi khó hiểu.
 */
define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: '');
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: '');
define('GOOGLE_REDIRECT_URI', getenv('GOOGLE_REDIRECT_URI') ?: 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . BASE_URL . '/actions/google_callback.php');

// Helper tạo URL tuyệt đối. BASE_URL cần khớp tên thư mục trong htdocs.
// Tự nhận diện host từ request (thay vì cố định "localhost"), để link hoạt
// động đúng cả khi truy cập bằng địa chỉ LAN (vd 192.168.1.5) từ điện thoại
// trong cùng mạng WiFi, không chỉ khi mở trên chính máy tính qua localhost.
function base_url($path = '') {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return 'http://' . $host . BASE_URL . '/' . ltrim($path, '/');
}
