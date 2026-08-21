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

    $movieIdRaw = trim((string) ($_POST['movie_id'] ?? ''));
    $movieId = ($movieIdRaw !== '' && ctype_digit($movieIdRaw)) ? (int) $movieIdRaw : null;

    $parentIdRaw = trim((string) ($_POST['parent_id'] ?? ''));
    $parentId = ($parentIdRaw !== '' && ctype_digit($parentIdRaw)) ? (int) $parentIdRaw : null;

    $content = trim((string) ($_POST['comment'] ?? ''));
    $redirectUrl = $_POST['redirect_to'] ?? '../index.php';

    if ($content !== '' && $userId > 0) {
        // Nếu là reply, kế thừa movie_id từ bình luận gốc để tránh sai lệch dữ liệu.
        if ($parentId) {
            $parent = db_select_one('SELECT movie_id FROM comments WHERE id = ?', [$parentId]);
            if ($parent) {
                $movieId = $parent['movie_id'];
            } else {
                $parentId = null;
            }
        }

        db_execute(
            'INSERT INTO comments (movie_id, user_id, parent_id, comment) VALUES (?, ?, ?, ?)',
            [$movieId, $userId, $parentId, mb_substr($content, 0, 2000)]
        );
        flash('Đã đăng bình luận');
    } else {
        flash('Nội dung bình luận không được để trống', 'error');
    }

    header('Location: ' . $redirectUrl);
    exit;
}

header('Location: ../index.php');
exit;
