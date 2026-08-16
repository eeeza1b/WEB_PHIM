<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/tmdb_helper.php';

function e($str) {
    return htmlspecialchars((string) $str, ENT_QUOTES, 'UTF-8');
}

function url($path = '') {
    return base_url($path);
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function csrf_validate() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            die('CSRF token validation failed.');
        }
    }
}

function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validate_username($username) {
    return preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username);
}

function validate_password($password) {
    return strlen($password) >= 6;
}

function flash($message, $type = 'success') {
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function render_flashes() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        echo '<div class="alert alert-' . e($flash['type']) . '">' . e($flash['message']) . '</div>';
    }
}

/* Giữ nguyên chức năng gợi ý phim theo Mood. */
function save_mood_cookie($mood_id) {
    setcookie('user_mood', $mood_id, COOKIE_EXPIRY, '/');
}

function get_mood_cookie() {
    return $_COOKIE['user_mood'] ?? null;
}

function get_all_genres() {
    return db_select('SELECT * FROM genres ORDER BY name');
}

function get_all_moods() {
    return db_select('SELECT * FROM moods ORDER BY name');
}

/* Lấy poster TMDB hoặc poster local; fallback khi phim không có ảnh. */
function movie_poster_url(?string $posterPath, string $size = 'w500'): string {
    $posterPath = trim((string) $posterPath);
    if ($posterPath === '') {
        return 'https://placehold.co/500x750/171d32/e7edff?text=No+Poster';
    }

    if (preg_match('#^https?://#i', $posterPath)) {
        return $posterPath;
    }

    if (str_starts_with($posterPath, '/')) {
        return 'https://image.tmdb.org/t/p/' . $size . $posterPath;
    }

    return base_url('assets/images/posters/' . rawurlencode($posterPath));
}

function movie_detail_url(int $movieId): string {
    return base_url('movie?id=' . $movieId);
}

function movie_genres(int $movieId): array {
    return db_select(
        'SELECT g.id, g.name
         FROM genres g
         INNER JOIN movie_genre mg ON mg.genre_id = g.id
         WHERE mg.movie_id = ?
         ORDER BY g.name',
        [$movieId]
    );
}

/* Dữ liệu thống nhất cho home/browse, gồm cả chuỗi tên thể loại. */
function get_movies(array $filters = [], int $page = 1, int $perPage = 10): array {
    $page = max(1, $page);
    $perPage = max(1, min(24, $perPage));
    $offset = ($page - 1) * $perPage;
    $params = [];

    $sql = 'SELECT m.*,
        GROUP_CONCAT(DISTINCT g.name ORDER BY g.name SEPARATOR \', \') AS genre_names
        FROM movies m
        LEFT JOIN movie_genre mg ON mg.movie_id = m.id
        LEFT JOIN genres g ON g.id = mg.genre_id';

    if (!empty($filters['genre'])) {
        $sql .= ' WHERE EXISTS (
            SELECT 1 FROM movie_genre filter_mg
            WHERE filter_mg.movie_id = m.id AND filter_mg.genre_id = ?
        )';
        $params[] = (int) $filters['genre'];
    }

    $sql .= ' GROUP BY m.id ORDER BY m.created_at DESC, m.id DESC LIMIT ' . $perPage . ' OFFSET ' . $offset;
    return db_select($sql, $params);
}

function count_movies(array $filters = []): int {
    $sql = 'SELECT COUNT(*) AS total FROM movies m';
    $params = [];

    if (!empty($filters['genre'])) {
        $sql .= ' WHERE EXISTS (
            SELECT 1 FROM movie_genre mg
            WHERE mg.movie_id = m.id AND mg.genre_id = ?
        )';
        $params[] = (int) $filters['genre'];
    }

    $row = db_select_one($sql, $params);
    return (int) ($row['total'] ?? 0);
}

function get_movie_with_genres(int $movieId): ?array {
    return db_select_one(
        'SELECT m.*, GROUP_CONCAT(DISTINCT g.name ORDER BY g.name SEPARATOR \', \') AS genre_names
         FROM movies m
         LEFT JOIN movie_genre mg ON mg.movie_id = m.id
         LEFT JOIN genres g ON g.id = mg.genre_id
         WHERE m.id = ?
         GROUP BY m.id',
        [$movieId]
    ) ?: null;
}

function render_pagination(int $total, int $page, int $perPage, string $basePath = 'browse', array $extra = []): void {
    $pages = max(1, (int) ceil($total / $perPage));
    if ($pages <= 1) return;

    echo '<nav class="pagination" aria-label="Phân trang">';
    for ($i = 1; $i <= $pages; $i++) {
        $query = array_filter(array_merge($extra, ['p' => $i]), static fn($value) => $value !== null && $value !== '');
        $href = base_url($basePath) . '?' . http_build_query($query);
        $active = $i === $page ? ' is-active' : '';
        echo '<a class="pagination-link' . $active . '" href="' . e($href) . '">' . $i . '</a>';
    }
    echo '</nav>';
}

function json_success($data) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'success', 'data' => $data], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function json_error($message, $code = 400) {
    header('Content-Type: application/json; charset=utf-8', true, $code);
    echo json_encode(['status' => 'error', 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}