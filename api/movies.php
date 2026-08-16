<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

/*
 * GET /api/movies.php?search=inception
 * Dùng LIKE với prepared statement, không nối trực tiếp dữ liệu người dùng vào SQL.
 */
header('Content-Type: application/json; charset=utf-8');

$search = trim((string) ($_GET['search'] ?? ''));
$limit = 8;

try {
    if ($search === '') {
        $movies = db_select(
            'SELECT id, title, release_year, poster_path, rating
             FROM movies
             ORDER BY created_at DESC
             LIMIT ' . $limit
        );
    } else {
        $movies = db_select(
            'SELECT id, title, release_year, poster_path, rating
             FROM movies
             WHERE title LIKE ?
             ORDER BY title ASC
             LIMIT ' . $limit,
            ['%' . $search . '%']
        );
    }

    echo json_encode(
        ['status' => 'success', 'data' => $movies],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode(
        ['status' => 'error', 'message' => 'Không thể tải danh sách phim.'],
        JSON_UNESCAPED_UNICODE
    );
}