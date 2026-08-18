<?php
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url('home'));
    exit;
}

csrf_validate();

$movieId = isset($_POST['movie_id']) && ctype_digit((string) $_POST['movie_id']) ? (int) $_POST['movie_id'] : 0;
$rating = isset($_POST['rating']) && ctype_digit((string) $_POST['rating']) ? (int) $_POST['rating'] : 0;
$redirectTo = (string) ($_POST['redirect_to'] ?? base_url('home'));

if ($movieId <= 0 || $rating < 1 || $rating > 5) {
    flash('Đánh giá không hợp lệ.', 'error');
    header('Location: ' . base_url('home'));
    exit;
}

$movie = db_select_one('SELECT id FROM movies WHERE id = ?', [$movieId]);
if (!$movie) {
    flash('Bộ phim không tồn tại.', 'error');
    header('Location: ' . base_url('home'));
    exit;
}

try {
    save_movie_rating($movieId, (int) current_user()['id'], $rating);
    flash('Đã lưu đánh giá ' . $rating . '/5 sao cho bộ phim.');
} catch (Throwable $e) {
    flash('Không thể lưu đánh giá lúc này.', 'error');
}

// Chỉ cho phép redirect nội bộ để tránh open redirect.
if (!str_starts_with($redirectTo, '/WEB_FILM-DEMO') && !str_starts_with($redirectTo, 'http://localhost/WEB_FILM-DEMO')) {
    $redirectTo = base_url('movie?id=' . $movieId);
}

header('Location: ' . $redirectTo);
exit;
