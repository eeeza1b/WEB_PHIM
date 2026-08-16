<?php
$selectedGenre = isset($_GET['genre']) && ctype_digit((string) $_GET['genre']) ? (int) $_GET['genre'] : null;
$currentPage = isset($_GET['p']) && ctype_digit((string) $_GET['p']) ? max(1, (int) $_GET['p']) : 1;
$perPage = 16;
$filters = ['genre' => $selectedGenre];
$movies = get_movies($filters, $currentPage, $perPage);
$totalMovies = count_movies($filters);

// Chỉ lấy các thể loại có ít nhất 1 bộ phim trong hệ thống
$genres = db_select("
    SELECT g.id, g.name, COUNT(mg.movie_id) AS total_movies
    FROM genres g
    INNER JOIN movie_genre mg ON g.id = mg.genre_id
    GROUP BY g.id, g.name
    HAVING COUNT(mg.movie_id) > 0
    ORDER BY g.name ASC
");
?>

<section class="page-header">
    <div class="container">
        <p class="eyebrow">THƯ VIỆN ĐIỆN ẢNH</p>
        <h1>Khám phá phim</h1>
        <p>Tìm bộ phim phù hợp với gu xem của bạn.</p>
    </div>
</section>

<section class="container section-block browse-layout">
    <form class="filter-bar" method="GET" action="<?php echo e(base_url('browse')); ?>">
        <label for="genre-filter">Lọc theo thể loại</label>
        <select id="genre-filter" name="genre" onchange="this.form.submit()">
            <option value="">Tất cả thể loại</option>
            <?php foreach ($genres as $genre): ?>
                <option value="<?php echo (int) $genre['id']; ?>" <?php echo $selectedGenre === (int) $genre['id'] ? 'selected' : ''; ?>>
                    <?php echo e($genre['name']); ?> (<?php echo (int) $genre['total_movies']; ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <?php if ($selectedGenre): ?>
            <a class="button button-ghost button-small" href="<?php echo e(base_url('browse')); ?>">Xóa lọc</a>
        <?php endif; ?>
        <span class="movie-count"><?php echo (int) $totalMovies; ?> phim</span>
    </form>

    <?php if ($movies): ?>
        <div class="movie-grid">
            <?php foreach ($movies as $movie): ?>
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
        <?php render_pagination($totalMovies, $currentPage, $perPage, 'browse', ['genre' => $selectedGenre]); ?>
    <?php else: ?>
        <div class="empty-state">
            <h2>Không tìm thấy phim phù hợp</h2>
            <p>Hãy chọn một thể loại khác hoặc xóa bộ lọc để xem toàn bộ thư viện.</p>
            <a class="button button-primary" href="<?php echo e(base_url('browse')); ?>">Xem tất cả phim</a>
        </div>
    <?php endif; ?>
</section>