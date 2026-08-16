<?php
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_admin();

$sql = "
    SELECT m.*, GROUP_CONCAT(g.name SEPARATOR ', ') AS genre_names
    FROM movies m
    LEFT JOIN movie_genre mg ON m.id = mg.movie_id
    LEFT JOIN genres g ON mg.genre_id = g.id
    GROUP BY m.id
    ORDER BY m.id DESC
";
$movies = db_select($sql);

require_once '../includes/header.php';
?>
<div class="container admin-container" style="max-width: 1200px; margin: 30px auto; padding: 0 15px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h2>Quản lý phim</h2>
        <a href="movie_add.php" class="btn btn-primary" style="padding: 8px 16px; background: #e50914; color: #fff; text-decoration: none; border-radius: 4px; font-weight: bold;">+ Thêm phim mới</a>
    </div>

    <div style="overflow-x: auto; background: #12151e; border-radius: 8px; border: 1px solid #232733;">
        <table class="table-admin" style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="border-bottom: 2px solid #2a2e3d; color: #8c93a8; font-size: 14px;">
                    <th style="padding: 14px 16px; width: 80px;">Poster</th>
                    <th style="padding: 14px 16px; min-width: 200px;">Tiêu đề</th>
                    <th style="padding: 14px 16px; width: 90px;">Năm</th>
                    <th style="padding: 14px 16px; width: 90px;">Điểm</th>
                    <th style="padding: 14px 16px;">Thể loại</th>
                    <th style="padding: 14px 16px; width: 130px; text-align: center; white-space: nowrap;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($movies)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 30px; color: #777;">Chưa có phim nào trong hệ thống.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($movies as $movie): ?>
                        <tr style="border-bottom: 1px solid #1c202d;">
                            <!-- Xử lý hiển thị Poster mượt mà, không bị vỡ khung -->
                            <td style="padding: 12px 16px;">
                                <?php if (!empty($movie['poster_path'])): ?>
                                    <img 
                                        src="../assets/images/posters/<?php echo e($movie['poster_path']); ?>" 
                                        alt="Poster" 
                                        style="width: 50px; height: 70px; object-fit: cover; border-radius: 4px; display: block; background: #1f2430;"
                                        onerror="this.onerror=null; this.parentElement.innerHTML='<div style=\'width:50px;height:70px;background:#1e222d;border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:10px;color:#666;\'>Trống</div>';"
                                    >
                                <?php else: ?>
                                    <div style="width: 50px; height: 70px; background: #1e222d; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 10px; color: #666;">
                                        Trống
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td style="padding: 12px 16px; font-weight: 600; color: #fff;">
                                <?php echo e($movie['title']); ?>
                            </td>
                            
                            <td style="padding: 12px 16px; color: #a0a6b5;">
                                <?php echo e((string)($movie['release_year'] ?? '—')); ?>
                            </td>
                            
                            <td style="padding: 12px 16px; color: #f5c518; font-weight: bold;">
                                ⭐ <?php echo e((string)($movie['rating'] ?? '0')); ?>
                            </td>
                            
                            <td style="padding: 12px 16px; color: #8e95a5; font-size: 13px; line-height: 1.4;">
                                <?php echo e($movie['genre_names'] ?? 'Chưa phân loại'); ?>
                            </td>

                            <!-- Cố định 2 nút Sửa và Xóa nằm ngang nhau -->
                            <td style="padding: 12px 16px; text-align: center; white-space: nowrap;">
                                <div style="display: inline-flex; align-items: center; gap: 12px;">
                                    <a href="movie_edit.php?id=<?php echo (int) $movie['id']; ?>" style="color: #3b82f6; text-decoration: none; font-size: 14px; font-weight: 500;">
                                        Sửa
                                    </a>
                                    <span style="color: #333;">|</span>
                                    <a href="movie_delete.php?id=<?php echo (int) $movie['id']; ?>" onclick="return confirm('Bạn có chắc chắn muốn xóa phim \'<?php echo addslashes($movie['title']); ?>\' không?');" style="color: #ef4444; text-decoration: none; font-size: 14px; font-weight: 500;">
                                        Xóa
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>