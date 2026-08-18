<?php
$movieId = isset($_GET['id']) && ctype_digit((string) $_GET['id']) ? (int) $_GET['id'] : 0;
$movie = $movieId ? get_movie_with_genres($movieId) : null;

if (!$movie):
?>
    <section class="container section-block">
        <div class="empty-state">
            <h1>Không tìm thấy phim</h1>
            <p>Bộ phim bạn tìm có thể đã bị xóa hoặc đường dẫn không chính xác.</p>
            <a class="button button-primary" href="<?php echo e(base_url('browse')); ?>">Quay lại thư viện</a>
        </div>
    </section>
<?php
    return;
endif;

$genres = movie_genres((int) $movie['id']);

// Ghi nhận lượt xem thực tế để các tab Đề cử có dữ liệu theo ngày/mùa/tháng.
$currentUser = current_user();
record_movie_view((int) $movie['id'], $currentUser ? (int) $currentUser['id'] : null);
$userMovieRating = $currentUser ? get_user_movie_rating((int) $movie['id'], (int) $currentUser['id']) : null;
$userRatingSummary = get_movie_user_rating_summary((int) $movie['id']);

// Ưu tiên lấy link Trailer từ cột trailer_url do Admin nhập, nếu không có mới tìm theo tmdb_id
$youtubeEmbedUrl = null;
if (!empty($movie['trailer_url'])) {
    $rawTrailer = trim($movie['trailer_url']);
    if (preg_match('/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))([\w-]{11})/', $rawTrailer, $matches)) {
        $youtubeEmbedUrl = 'https://www.youtube.com/embed/' . $matches[1];
    } elseif (preg_match('/^[\w-]{11}$/', $rawTrailer)) {
        $youtubeEmbedUrl = 'https://www.youtube.com/embed/' . $rawTrailer;
    } else {
        $youtubeEmbedUrl = $rawTrailer;
    }
} elseif (!empty($movie['tmdb_id']) && function_exists('get_youtube_trailer_url')) {
    $youtubeEmbedUrl = get_youtube_trailer_url($movie['tmdb_id']);
}

// Kiểm tra trạng thái Watchlist
$inWatchlist = false;
if (is_logged_in()) {
    $user = current_user();
    $check = db_select_one(
        'SELECT 1 FROM watchlist WHERE user_id = ? AND movie_id = ?',
        [$user['id'], $movie['id']]
    );
    $inWatchlist = !empty($check);
}
?>

<section class="movie-detail-hero">
    <div class="container movie-detail-layout">
        <div class="detail-poster">
            <img src="<?php echo e(movie_poster_url($movie['poster_path'] ?? null, 'w780')); ?>"
                alt="Poster phim <?php echo e($movie['title']); ?>">
        </div>

        <div class="detail-content">
            <a class="back-link" href="<?php echo e(base_url('browse')); ?>">← Quay lại khám phá</a>
            <p class="eyebrow">MOVIE DETAILS</p>
            <h1><?php echo e($movie['title']); ?></h1>

            <div class="detail-stat-list">
                <span class="detail-rating">★ <?php echo e(number_format((float) $movie['rating'], 1)); ?> <small>/ 10</small></span>
                <span><?php echo e($movie['release_year'] ?: 'Đang cập nhật'); ?></span>
                <span><?php echo e($movie['genre_names'] ?: 'Chưa phân loại'); ?></span>
            </div>

            <div class="movie-overview">
                <?php echo !empty($movie['description']) ? html_entity_decode($movie['description']) : '<p>Nội dung đang được cập nhật.</p>'; ?>
            </div>

            <?php if ($genres): ?>
                <div class="genre-tags" aria-label="Thể loại phim">
                    <?php foreach ($genres as $genre): ?>
                        <a href="<?php echo e(base_url('browse') . '?genre=' . (int) $genre['id']); ?>"><?php echo e($genre['name']); ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="movie-user-rating">
                <div class="movie-user-rating__summary">
                    <strong>Đánh giá của người xem</strong>
                    <?php if ($userRatingSummary['count'] > 0): ?>
                        <span>★ <?php echo e(number_format((float) $userRatingSummary['average'], 1)); ?>/5 · <?php echo (int) $userRatingSummary['count']; ?> lượt đánh giá</span>
                    <?php else: ?>
                        <span>Chưa có lượt đánh giá</span>
                    <?php endif; ?>
                </div>
                <?php if (is_logged_in()): ?>
                    <form action="<?php echo e(base_url('actions/movie_rating.php')); ?>" method="POST" class="movie-rating-form">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="movie_id" value="<?php echo (int) $movie['id']; ?>">
                        <input type="hidden" name="redirect_to" value="<?php echo e($_SERVER['REQUEST_URI']); ?>">
                        <div class="star-rating" role="radiogroup" aria-label="Đánh giá phim từ 1 đến 5 sao">
                            <?php for ($star = 5; $star >= 1; $star--): ?>
                                <input type="radio" id="movie-rating-<?php echo $star; ?>" name="rating" value="<?php echo $star; ?>" <?php echo $userMovieRating === $star ? 'checked' : ''; ?>>
                                <label for="movie-rating-<?php echo $star; ?>" title="<?php echo $star; ?> sao">★</label>
                            <?php endfor; ?>
                        </div>
                        <button type="submit" class="button button-ghost button-small"><?php echo $userMovieRating ? 'Cập nhật đánh giá' : 'Đánh giá'; ?></button>
                    </form>
                <?php else: ?>
                    <a class="movie-user-rating__login" href="<?php echo e(base_url('login')); ?>">Đăng nhập để đánh giá</a>
                <?php endif; ?>
            </div>

            <div class="detail-actions" style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                <?php if ($youtubeEmbedUrl): ?>
                    <button class="button button-primary js-open-trailer"
                        type="button"
                        data-embed-url="<?php echo e($youtubeEmbedUrl); ?>"
                        data-movie-id="<?php echo (int) $movie['id']; ?>"
                        data-movie-title="<?php echo e($movie['title']); ?>">
                        <span aria-hidden="true">▶</span> Xem trailer
                    </button>
                <?php else: ?>
                    <span class="trailer-unavailable">Trailer đang cập nhật</span>
                <?php endif; ?>

                <?php if (is_logged_in()): ?>
                    <form action="actions/watchlist_toggle.php" method="POST" style="display: inline; margin: 0;">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="movie_id" value="<?php echo (int) $movie['id']; ?>">
                        <input type="hidden" name="redirect_to" value="<?php echo e($_SERVER['REQUEST_URI']); ?>">
                        <button type="submit" class="button <?php echo $inWatchlist ? 'button-secondary' : 'button-ghost'; ?>" style="cursor: pointer;">
                            <?php echo $inWatchlist ? '✓ Đã lưu vào danh sách' : '+ Danh sách xem'; ?>
                        </button>
                    </form>
                <?php else: ?>
                    <a class="button button-ghost" href="<?php echo e(base_url('login')); ?>">Đăng nhập để lưu</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<div id="trailer-modal" class="trailer-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="trailer-modal-title">
    <div class="trailer-modal__backdrop" data-close-trailer></div>
    <div class="trailer-modal__dialog">
        <div class="trailer-modal__header">
            <h2 id="trailer-modal-title">Trailer — <?php echo e($movie['title']); ?></h2>
            <button class="trailer-modal__close" type="button" aria-label="Đóng trailer" data-close-trailer>×</button>
        </div>
        <div id="trailer-modal-body" class="trailer-modal__body"></div>
    </div>
</div>

<?php
// Gợi ý phim cùng thể loại, hiển thị ngay dưới phần chi tiết/trailer.
$relatedPage = isset($_GET['p']) && ctype_digit((string) $_GET['p']) ? (int) $_GET['p'] : 1;
$relatedPage = max(1, $relatedPage);
$relatedPerPage = 20;
$relatedTotal = count_related_movies((int) $movie['id']);
$relatedMovies = $relatedTotal > 0
    ? get_related_movies((int) $movie['id'], $relatedPage, $relatedPerPage)
    : [];
$relatedPages = max(1, (int) ceil($relatedTotal / $relatedPerPage));
if ($relatedPage > $relatedPages) {
    $relatedPage = $relatedPages;
    $relatedMovies = get_related_movies((int) $movie['id'], $relatedPage, $relatedPerPage);
}
?>

<?php if ($relatedTotal > 0): ?>
    <section id="related-movies" class="container section-block related-movies-section">
        <div class="section-heading related-movies-heading">
            <div>
                <p class="eyebrow">GỢI Ý CHO BẠN</p>
                <h2>Có thể bạn cũng thích</h2>
                <p>Các bộ phim có cùng thể loại với <strong><?php echo e($movie['title']); ?></strong>.</p>
            </div>
        </div>

        <div class="related-carousel" data-related-carousel>
            <button class="related-carousel__arrow related-carousel__arrow--prev" type="button" data-related-prev aria-label="Phim đề xuất trước">‹</button>
            <div class="related-carousel__viewport" data-related-viewport>
                <?php render_movie_grid($relatedMovies); ?>
            </div>
            <button class="related-carousel__arrow related-carousel__arrow--next" type="button" data-related-next aria-label="Phim đề xuất tiếp theo">›</button>
        </div>

        <?php
        render_pagination(
            $relatedTotal,
            $relatedPage,
            $relatedPerPage,
            'movie',
            ['id' => (int) $movie['id']]
        );
        ?>
    </section>
<?php endif; ?>

<?php render_comment_block((int) $movie['id'], 'comments'); ?>