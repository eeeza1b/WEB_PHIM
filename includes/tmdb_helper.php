<?php
declare(strict_types=1);

/**
 * TMDB API helper dùng Bearer Token (API Read Access Token v4) hoặc API Key v3.
 * Hàm trả về mảng PHP và ném RuntimeException khi API không khả dụng.
 */

function tmdb_request(string $path, array $query = []): array
{
    $token = defined('TMDB_API_TOKEN') ? trim((string) TMDB_API_TOKEN) : '';
    $apiKey = defined('TMDB_API_KEY') ? trim((string) TMDB_API_KEY) : '';

    $hasBearerToken = $token !== ''
        && $token !== 'PASTE_YOUR_TMDB_READ_ACCESS_TOKEN_HERE';
    $hasApiKey = $apiKey !== ''
        && $apiKey !== 'PASTE_YOUR_TMDB_API_KEY_HERE';

    if (!$hasBearerToken && !$hasApiKey) {
        throw new RuntimeException(
            'Chưa cấu hình TMDB_API_KEY (v3) hoặc TMDB_API_TOKEN (v4) trong config.php.'
        );
    }

    // Key v3 truyền qua query string; token v4 dùng Authorization Bearer header.
    if (!$hasBearerToken) {
        $query['api_key'] = $apiKey;
    }

    $url = rtrim(TMDB_API_BASE_URL, '/') . '/' . ltrim($path, '/');
    if ($query !== []) {
        $url .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    $curl = curl_init($url);
    if ($curl === false) {
        throw new RuntimeException('Không thể khởi tạo cURL.');
    }

    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => array_filter([
            'Accept: application/json',
            $hasBearerToken ? 'Authorization: Bearer ' . $token : null,
        ]),
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_FAILONERROR => false,
    ]);

    $body = curl_exec($curl);
    $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);

    if ($body === false) {
        throw new RuntimeException('Lỗi kết nối TMDB: ' . $curlError);
    }

    $data = json_decode($body, true);
    if (!is_array($data)) {
        throw new RuntimeException('TMDB trả về JSON không hợp lệ.');
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        $message = $data['status_message'] ?? 'Lỗi HTTP ' . $httpCode;
        throw new RuntimeException('TMDB API: ' . $message);
    }

    return $data;
}

/** Lấy danh sách phim phổ biến từ /movie/popular. */
function tmdb_get_popular_movies(int $page = 1, string $language = 'vi-VN'): array
{
    try {
        $data = tmdb_request('/movie/popular', [
            'language' => $language,
            'page' => max(1, $page),
        ]);
        return $data['results'] ?? [];
    } catch (\Throwable $e) {
        return [];
    }
}

/** Trả về map [tmdbGenreId => tên thể loại] phục vụ đồng bộ movie_genre. */
function tmdb_get_movie_genres(string $language = 'en-US'): array
{
    try {
        $data = tmdb_request('/genre/movie/list', ['language' => $language]);
        $genres = [];

        foreach (($data['genres'] ?? []) as $genre) {
            if (isset($genre['id'], $genre['name'])) {
                $genres[(int) $genre['id']] = (string) $genre['name'];
            }
        }
        return $genres;
    } catch (\Throwable $e) {
        return [];
    }
}

/**
 * Lấy trailer YouTube ưu tiên bản chính thức và đúng ngôn ngữ.
 * Bọc Try-Catch an toàn tuyệt đối: Không bao giờ làm sập trang web hay bung lỗi Fatal Error.
 */
function get_youtube_trailer_url(?int $tmdbMovieId): ?string {
    if (!$tmdbMovieId) {
        return null;
    }

    try {
        $languages = ['vi-VN', 'en-US'];
        foreach ($languages as $lang) {
            $data = tmdb_request("/movie/{$tmdbMovieId}/videos", ['language' => $lang]);
            if (!empty($data['results']) && is_array($data['results'])) {
                foreach ($data['results'] as $video) {
                    if (($video['site'] ?? '') === 'YouTube' && ($video['type'] ?? '') === 'Trailer' && !empty($video['key'])) {
                        return 'https://www.youtube.com/embed/' . $video['key'];
                    }
                }
            }
        }
    } catch (\Throwable $e) {
        // Nếu API Key sai hoặc mạng có sự cố, im lặng trả về null để trang web vẫn hiển thị bình thường.
        return null;
    }

    return null;
}

function tmdb_get_movie_trailer(int $tmdbMovieId, string $language = 'vi-VN'): ?array
{
    try {
        $data = tmdb_request('/movie/' . $tmdbMovieId . '/videos', ['language' => $language]);
        $videos = $data['results'] ?? [];

        foreach ($videos as $video) {
            if (($video['site'] ?? '') === 'YouTube' && ($video['type'] ?? '') === 'Trailer' && !empty($video['key'])) {
                return [
                    'key' => $video['key'],
                    'name' => $video['name'] ?? 'Trailer',
                    'url' => 'https://www.youtube.com/watch?v=' . $video['key'],
                ];
            }
        }
    } catch (\Throwable $e) {
        return null;
    }

    return null;
}

/** Wrapper tương thích với skeleton cũ. */
function get_tmdb_movie_data(string $movie_title): array
{
    try {
        $data = tmdb_request('/search/movie', [
            'query' => $movie_title,
            'language' => 'vi-VN',
            'page' => 1,
        ]);
        return $data['results'] ?? [];
    } catch (\Throwable $e) {
        return [];
    }
}