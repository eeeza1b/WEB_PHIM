<?php
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();

    $user = current_user();
    $userId = (int) ($user['id'] ?? 0);
    $movieId = (int) ($_POST['movie_id'] ?? 0);
    $redirectUrl = $_POST['redirect_to'] ?? '../index.php';

    if ($movieId > 0 && $userId > 0) {
        $exists = db_select_one(
            'SELECT 1 FROM watchlist WHERE user_id = ? AND movie_id = ?',
            [$userId, $movieId]
        );

        if ($exists) {
            db_execute('DELETE FROM watchlist WHERE user_id = ? AND movie_id = ?', [$userId, $movieId]);
            flash('Đã xóa phim khỏi danh sách xem.');
        } else {
            db_execute('INSERT INTO watchlist (user_id, movie_id) VALUES (?, ?)', [$userId, $movieId]);
            flash('Đã thêm phim vào danh sách xem!');
        }
    }

    header('Location: ' . $redirectUrl);
    exit;
}

header('Location: ../index.php');
exit;