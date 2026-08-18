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
    $commentId = (int) ($_POST['comment_id'] ?? 0);
    $reaction = ($_POST['reaction'] ?? '') === 'dislike' ? 'dislike' : 'like';
    $redirectUrl = $_POST['redirect_to'] ?? '../index.php';

    if ($commentId > 0 && $userId > 0) {
        $existing = db_select_one(
            'SELECT reaction FROM comment_reactions WHERE comment_id = ? AND user_id = ?',
            [$commentId, $userId]
        );

        if (!$existing) {
            // Chưa có lượt nào -> thêm mới, tăng đếm tương ứng.
            db_execute('INSERT INTO comment_reactions (comment_id, user_id, reaction) VALUES (?, ?, ?)', [$commentId, $userId, $reaction]);
            $column = $reaction === 'like' ? 'likes_count' : 'dislikes_count';
            db_execute("UPDATE comments SET {$column} = {$column} + 1 WHERE id = ?", [$commentId]);
        } elseif ($existing['reaction'] === $reaction) {
            // Bấm lại đúng loại đã chọn -> huỷ lượt (un-like / un-dislike).
            db_execute('DELETE FROM comment_reactions WHERE comment_id = ? AND user_id = ?', [$commentId, $userId]);
            $column = $reaction === 'like' ? 'likes_count' : 'dislikes_count';
            db_execute("UPDATE comments SET {$column} = GREATEST({$column} - 1, 0) WHERE id = ?", [$commentId]);
        } else {
            // Đổi từ like sang dislike (hoặc ngược lại) -> cập nhật, giảm cột cũ tăng cột mới.
            db_execute('UPDATE comment_reactions SET reaction = ? WHERE comment_id = ? AND user_id = ?', [$reaction, $commentId, $userId]);
            $oldColumn = $reaction === 'like' ? 'dislikes_count' : 'likes_count';
            $newColumn = $reaction === 'like' ? 'likes_count' : 'dislikes_count';
            db_execute("UPDATE comments SET {$oldColumn} = GREATEST({$oldColumn} - 1, 0), {$newColumn} = {$newColumn} + 1 WHERE id = ?", [$commentId]);
        }
    }

    header('Location: ' . $redirectUrl);
    exit;
}

header('Location: ../index.php');
exit;
