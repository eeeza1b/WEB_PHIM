<?php
declare(strict_types=1);

/**
 * Đồng bộ một trang TMDB Popular vào MySQL.
 * Chạy: php tools/sync_tmdb_movies.php 1
 *
 * Không tạo/chỉnh movie_mood để bảo toàn tính năng gợi ý theo Mood của dự án.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Script này chỉ chạy bằng CLI.\n");
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/tmdb_helper.php';

$page = isset($argv[1]) ? max(1, (int) $argv[1]) : 1;

function tmdb_sync_slug(string $title, int $tmdbId): string
{
    $asciiTitle = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $title) ?: $title;
    $slug = strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $asciiTitle));
    $slug = trim($slug, '-');

    return 'tmdb-' . $tmdbId . '-' . ($slug !== '' ? $slug : 'movie');
}

try {
    $movies = tmdb_get_popular_movies($page);
    $tmdbGenres = tmdb_get_movie_genres();
    $pdo = db();

    $inserted = 0;
    $skipped = 0;

    foreach ($movies as $tmdbMovie) {
        $tmdbId = (int) ($tmdbMovie['id'] ?? 0);
        $title = trim((string) ($tmdbMovie['title'] ?? ''));

        if ($tmdbId <= 0 || $title === '') {
            ++$skipped;
            continue;
        }

        $slug = tmdb_sync_slug($title, $tmdbId);
        $existing = db_select_one('SELECT id FROM movies WHERE slug = ?', [$slug]);

        if ($existing) {
            ++$skipped;
            continue;
        }

        $releaseDate = (string) ($tmdbMovie['release_date'] ?? '');
        $releaseYear = preg_match('/^\d{4}/', $releaseDate, $matches) ? (int) $matches[0] : null;
        $rating = isset($tmdbMovie['vote_average']) ? round((float) $tmdbMovie['vote_average'], 1) : null;

        $pdo->beginTransaction();

        try {
            $movieId = (int) db_insert(
                'INSERT INTO movies (tmdb_id, title, slug, description, release_year, poster_path, rating)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [
                    $tmdbId,
                    $title,
                    $slug,
                    (string) ($tmdbMovie['overview'] ?? ''),
                    $releaseYear,
                    $tmdbMovie['poster_path'] ?: null,
                    $rating,
                ]
            );

            foreach (($tmdbMovie['genre_ids'] ?? []) as $tmdbGenreId) {
                $genreName = $tmdbGenres[(int) $tmdbGenreId] ?? null;
                if ($genreName === null) {
                    continue;
                }

                db_execute('INSERT IGNORE INTO genres (name) VALUES (?)', [$genreName]);
                $genre = db_select_one('SELECT id FROM genres WHERE name = ?', [$genreName]);

                if ($genre) {
                    db_execute(
                        'INSERT IGNORE INTO movie_genre (movie_id, genre_id) VALUES (?, ?)',
                        [$movieId, $genre['id']]
                    );
                }
            }

            $pdo->commit();
            ++$inserted;
            echo "[INSERTED] {$title} (TMDB #{$tmdbId})\n";
        } catch (Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }

    echo "\nHoàn tất trang {$page}: {$inserted} phim mới, {$skipped} phim bỏ qua.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, 'Đồng bộ thất bại: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}