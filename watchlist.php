<?php
require_login();

$user = current_user();
$userId = (int) $user['id'];

// Truy vấn danh sách phim không dùng w.created_at
$sql = "
    SELECT m.*, GROUP_CONCAT(g.name SEPARATOR ', ') AS genre_names
    FROM watchlist w
    JOIN movies m ON w.movie_id = m.id
    LEFT JOIN movie_genre mg ON m.id = mg.movie_id
    LEFT JOIN genres g ON mg.genre_id = g.id
    WHERE w.user_id = ?
    GROUP BY m.id
    ORDER BY m.id DESC
";
$movies = db_select($sql, [$userId]);
?>

<div class="container section-block" style="max-width: 1200px; margin: 30px auto; padding: 0 15px;">
    <h2 style="margin-bottom: 24px; color: #fff;">Danh sách phim của bạn</h2>

    <?php if (empty($movies)): ?>
        <div class="empty-state" style="background: #12151e; border: 1px solid #232733; border-radius: 8px; text-align: center; padding: 50px 20px;">
            <p style="font-size: 16px; color: #8c93a8; margin-bottom: 20px;">Danh sách xem của bạn đang trống.</p>
            <a href="<?php echo e(base_url('browse')); ?>" class="button button-primary" style="background: #e50914; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block;">Khám phá phim ngay</a>
        </div>
    <?php else: ?>
        <div class="movie-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px;">
            <?php foreach ($movies as $movie): ?>
                <div class="movie-card" style="background: #12151e; border: 1px solid #232733; border-radius: 8px; overflow: hidden; display: flex; flex-direction: column;">
                    <a href="<?php echo e(base_url('movie') . '?id=' . (int)$movie['id']); ?>">
                        <img 
                            src="<?php echo e(movie_poster_url($movie['poster_path'] ?? null, 'w342')); ?>" 
                            alt="Poster <?php echo e($movie['title']); ?>" 
                            style="width: 100%; height: 280px; object-fit: cover;"
                        >
                    </a>
                    
                    <div style="padding: 15px; flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <h4 style="margin: 0 0 8px 0; font-size: 15px; font-weight: 600;">
                                <a href="<?php echo e(base_url('movie') . '?id=' . (int)$movie['id']); ?>" style="color: #fff; text-decoration: none;">
                                    <?php echo e($movie['title']); ?>
                                </a>
                            </h4>
                            <div style="font-size: 13px; color: #f5c518; margin-bottom: 5px; font-weight: bold;">
                                ★ <?php echo e(number_format(movie_display_rating($movie), 1)); ?> 
                                <span style="color: #8c93a8; font-weight: normal; margin-left: 5px;">| <?php echo e((string)($movie['release_year'] ?? '')); ?></span>
                            </div>
                            <div class="movie-view-count">👁 <?php echo number_format(movie_display_views($movie)); ?> lượt xem</div>
                        </div>

                        <!-- Nút Bỏ lưu -->
                        <form action="actions/watchlist_toggle.php" method="POST" style="margin-top: 10px;">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="movie_id" value="<?php echo (int)$movie['id']; ?>">
                            <input type="hidden" name="redirect_to" value="<?php echo e(base_url('watchlist')); ?>">
                            <button type="submit" style="width: 100%; padding: 8px; background: transparent; border: 1px solid #ef4444; color: #ef4444; border-radius: 4px; cursor: pointer; font-size: 13px;">
                                ✕ Bỏ lưu
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>