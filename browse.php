<?php
$selectedGenre = isset($_GET['genre']) && ctype_digit((string) $_GET['genre']) ? (int) $_GET['genre'] : null;
$selectedYear = isset($_GET['year']) && ctype_digit((string) $_GET['year']) ? (int) $_GET['year'] : null;

$currentPage = isset($_GET['p']) && ctype_digit((string) $_GET['p']) ? max(1, (int) $_GET['p']) : 1;
$perPage = 20; // 20 phim/trang; phân trang theo nhóm 7 trang.
$filters = ['genre' => $selectedGenre, 'year' => $selectedYear];
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

// Chỉ lấy các năm phát hành thực sự có phim trong hệ thống
$years = db_select("
    SELECT release_year, COUNT(*) AS total_movies
    FROM movies
    WHERE release_year IS NOT NULL
    GROUP BY release_year
    ORDER BY release_year DESC
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
        <div class="filter-group-label">Thể loại</div>
        <div class="filter-dropdown">
            <?php
            $genreAllQuery = array_filter(['year' => $selectedYear], static fn($value) => $value !== null && $value !== '');
            $genreAllHref = base_url('browse') . ($genreAllQuery ? '?' . http_build_query($genreAllQuery) : '');
            $selectedGenreName = 'Tất cả thể loại';
            foreach ($genres as $genre) {
                if ($selectedGenre === (int) $genre['id']) {
                    $selectedGenreName = $genre['name'];
                    break;
                }
            }
            ?>
            <button class="filter-dropdown__toggle" type="button" aria-haspopup="true">
                <span><?php echo e($selectedGenreName); ?></span>
                <span class="filter-dropdown__caret" aria-hidden="true">⌄</span>
            </button>
            <div class="filter-dropdown__panel filter-dropdown__panel--cols3">
                <a href="<?php echo e($genreAllHref); ?>" class="<?php echo $selectedGenre === null ? 'is-selected' : ''; ?>">Tất cả thể loại</a>
                <?php foreach ($genres as $genre): ?>
                    <?php
                    $genreQuery = array_filter(
                        ['genre' => (int) $genre['id'], 'year' => $selectedYear],
                        static fn($value) => $value !== null && $value !== ''
                    );
                    ?>
                    <a href="<?php echo e(base_url('browse') . '?' . http_build_query($genreQuery)); ?>" class="<?php echo $selectedGenre === (int) $genre['id'] ? 'is-selected' : ''; ?>">
                        <?php echo e($genre['name']); ?> <span>(<?php echo (int) $genre['total_movies']; ?>)</span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="filter-group-label filter-group-label--year">Năm</div>
        <div class="filter-dropdown filter-dropdown--year">
            <?php
            $yearAllQuery = array_filter(['genre' => $selectedGenre], static fn($value) => $value !== null && $value !== '');
            $yearAllHref = base_url('browse') . ($yearAllQuery ? '?' . http_build_query($yearAllQuery) : '');
            ?>
            <button class="filter-dropdown__toggle" type="button" aria-haspopup="true">
                <span><?php echo $selectedYear ? (int) $selectedYear : 'Tất cả năm'; ?></span>
                <span class="filter-dropdown__caret" aria-hidden="true">⌄</span>
            </button>
            <div class="filter-dropdown__panel filter-dropdown__panel--cols3">
                <a href="<?php echo e($yearAllHref); ?>" class="<?php echo $selectedYear === null ? 'is-selected' : ''; ?>">Tất cả năm</a>
                <?php foreach ($years as $year): ?>
                    <?php
                    $yearQuery = array_filter(
                        ['genre' => $selectedGenre, 'year' => (int) $year['release_year']],
                        static fn($value) => $value !== null && $value !== ''
                    );
                    ?>
                    <a href="<?php echo e(base_url('browse') . '?' . http_build_query($yearQuery)); ?>" class="<?php echo $selectedYear === (int) $year['release_year'] ? 'is-selected' : ''; ?>">
                        <?php echo (int) $year['release_year']; ?> <span>(<?php echo (int) $year['total_movies']; ?>)</span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($selectedGenre || $selectedYear): ?>
            <a class="button button-ghost button-small filter-clear" href="<?php echo e(base_url('browse')); ?>">Xóa lọc</a>
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
                        <span class="movie-rating">★ <?php echo e(number_format(movie_display_rating($movie), 1)); ?></span>
                        <span class="movie-play-icon" aria-hidden="true">▶</span>
                    </a>
                    <div class="movie-card-info">
                        <p class="movie-meta"><?php echo e($movie['release_year'] ?: 'Đang cập nhật'); ?> · <?php echo e($movie['genre_names'] ?: 'Chưa phân loại'); ?></p>
                        <h3><a href="<?php echo e(movie_detail_url((int) $movie['id'])); ?>"><?php echo e($movie['title']); ?></a></h3>
                        <p class="movie-view-count" aria-label="Lượt xem">👁 <?php echo number_format(movie_display_views($movie)); ?> lượt xem</p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <?php render_pagination($totalMovies, $currentPage, $perPage, 'browse', ['genre' => $selectedGenre, 'year' => $selectedYear]); ?>
    <?php else: ?>
        <div class="empty-state">
            <h2>Không tìm thấy phim phù hợp</h2>
            <p>Hãy chọn bộ lọc khác hoặc xóa bộ lọc để xem toàn bộ thư viện.</p>
            <a class="button button-primary" href="<?php echo e(base_url('browse')); ?>">Xem tất cả phim</a>
        </div>
    <?php endif; ?>
</section>