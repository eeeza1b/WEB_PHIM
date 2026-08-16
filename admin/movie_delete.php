<?php
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_admin();

$movieId = (int) ($_GET['id'] ?? 0);

if ($movieId > 0) {
    try {
        // Lấy tên poster trước khi xóa bản ghi
        $movie = db_select_one('SELECT poster_path FROM movies WHERE id = ?', [$movieId]);

        $pdo = db();
        $pdo->beginTransaction();

        // Xóa liên kết thể loại và xóa phim
        db_execute('DELETE FROM movie_genre WHERE movie_id = ?', [$movieId]);
        db_execute('DELETE FROM movies WHERE id = ?', [$movieId]);

        $pdo->commit();

        // Xóa file ảnh poster vật lý trong thư mục assets
        if ($movie && !empty($movie['poster_path'])) {
            $filePath = __DIR__ . '/../assets/images/posters/' . $movie['poster_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        flash('Đã xóa phim thành công!');
    } catch (Throwable $e) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        flash('Không thể xóa phim này.', 'error');
    }
}

header('Location: movies.php');
exit;