<?php
// Lấy 8 bộ phim có điểm rating cao nhất (sắp xếp giảm dần từ 10.0 -> 0.0)
$featuredMovies = db_select("
    SELECT m.*, GROUP_CONCAT(DISTINCT g.name SEPARATOR ', ') AS genre_names
    FROM movies m
    LEFT JOIN movie_genre mg ON m.id = mg.movie_id
    LEFT JOIN genres g ON mg.genre_id = g.id
    GROUP BY m.id
    ORDER BY CAST(m.rating AS DECIMAL(3,1)) DESC, m.id DESC
    LIMIT 8
");

$moods = get_all_moods();
?>

<section class="hero-section">
    <div class="container hero-content">
        <p class="eyebrow">KHÁM PHÁ ĐIỆN ẢNH MỖI NGÀY</p>
        <h1>Chọn một bộ phim<br><span>đúng với cảm xúc của bạn.</span></h1>
        <p class="hero-description">
            Khám phá những bộ phim nổi bật, lưu lại danh sách yêu thích
            và nhận gợi ý phù hợp với tâm trạng hôm nay.
        </p>
        <div class="hero-actions">
            <a class="button button-primary" href="<?php echo e(base_url('browse')); ?>">Khám phá phim</a>
            <a class="button button-ghost" href="#mood-picker">Chọn tâm trạng</a>
        </div>
    </div>
</section>

<section id="mood-picker" class="container section-block">
    <div class="mood-panel">
        <div class="section-heading mood-heading">
            <div>
                <p class="eyebrow">MOOD RECOMMENDATION</p>
                <h2>Hôm nay bạn cảm thấy thế nào?</h2>
                <p>Chọn một tâm trạng để nhận gợi ý phim dành riêng cho bạn.</p>
            </div>
            <span class="mood-icon" aria-hidden="true">✦</span>
        </div>

        <div class="mood-list" style="display: flex; gap: 10px; flex-wrap: wrap;">
            <?php if (!empty($moods)): ?>
                <?php foreach ($moods as $mood): 
                    $slug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $mood['name'] ?? ''));
                ?>
                    <a href="<?php echo e(base_url('mood') . '?mood=' . urlencode($slug) . '&mood_id=' . (int)$mood['id']); ?>" class="mood-chip" style="text-decoration: none; display: inline-block;">
                        <?php echo e($mood['name']); ?>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <a href="<?php echo e(base_url('mood') . '?mood=happy'); ?>" class="mood-chip" style="text-decoration: none;">😊 Happy</a>
                <a href="<?php echo e(base_url('mood') . '?mood=relaxing'); ?>" class="mood-chip" style="text-decoration: none;">🌿 Relaxing</a>
                <a href="<?php echo e(base_url('mood') . '?mood=romantic'); ?>" class="mood-chip" style="text-decoration: none;">💖 Romantic</a>
                <a href="<?php echo e(base_url('mood') . '?mood=sad'); ?>" class="mood-chip" style="text-decoration: none;">🌧️ Sad</a>
                <a href="<?php echo e(base_url('mood') . '?mood=thrilling'); ?>" class="mood-chip" style="text-decoration: none;">⚡ Thrilling</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="container section-block">
    <div class="section-heading">
        <div>
            <p class="eyebrow">PHIM NỔI BẬT</p>
            <h2>Đánh giá cao nhất</h2>
        </div>
        <a class="text-link" href="<?php echo e(base_url('browse')); ?>">Xem tất cả <span aria-hidden="true">→</span></a>
    </div>

    <?php if ($featuredMovies): ?>
        <div class="movie-grid">
            <?php foreach ($featuredMovies as $movie): ?>
                <article class="movie-card">
                    <a class="movie-poster" href="<?php echo e(movie_detail_url((int) $movie['id'])); ?>" aria-label="Xem chi tiết <?php echo e($movie['title']); ?>">
                        <img src="<?php echo e(movie_poster_url($movie['poster_path'] ?? null)); ?>"
                            alt="Poster phim <?php echo e($movie['title']); ?>" loading="lazy">
                        <span class="movie-rating">★ <?php echo e(number_format((float) $movie['rating'], 1)); ?></span>
                        <span class="movie-play-icon" aria-hidden="true">▶</span>
                    </a>
                    <div class="movie-card-info">
                        <p class="movie-meta"><?php echo e($movie['release_year'] ?: 'Đang cập nhật'); ?> · <?php echo e($movie['genre_names'] ?: 'Chưa phân loại'); ?></p>
                        <h3><a href="<?php echo e(movie_detail_url((int) $movie['id'])); ?>"><?php echo e($movie['title']); ?></a></h3>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">Chưa có dữ liệu phim trong hệ thống.</div>
    <?php endif; ?>
</section>