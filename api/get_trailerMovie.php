<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/tmdb_helper.php';

$movieId = filter_input(INPUT_GET, 'movie_id', FILTER_VALIDATE_INT);

if (!$movieId || $movieId < 1) {
    json_error('movie_id không hợp lệ.', 422);
}

$movie = db_select_one(
    'SELECT id, title, tmdb_id FROM movies WHERE id = ?',
    [$movieId]
);

if (!$movie) {
    json_error('Không tìm thấy phim.', 404);
}

if (empty($movie['tmdb_id'])) {
    json_error('Phim này chưa có trailer TMDB.', 404);
}

try {
    $trailer = tmdb_get_movie_trailer((int) $movie['tmdb_id']);

    if (!$trailer || empty($trailer['key'])) {
        json_error('TMDB chưa có trailer YouTube cho phim này.', 404);
    }

    json_success([
        'movie_id' => (int) $movie['id'],
        'title' => $movie['title'],
        'name' => $trailer['name'],
        'youtube_key' => $trailer['key'],
        'embed_url' => 'https://www.youtube-nocookie.com/embed/' . rawurlencode($trailer['key']) . '?autoplay=1&rel=0',
        'url' => $trailer['url'],
    ]);
} catch (Throwable $exception) {
    error_log('TMDB trailer error: ' . $exception->getMessage());
    json_error('Không thể tải trailer lúc này. Vui lòng thử lại sau.', 503);
}