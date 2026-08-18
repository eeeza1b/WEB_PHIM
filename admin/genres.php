<?php
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_admin();

$editingGenre = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();

    $action = $_POST['action'] ?? '';
    $genreId = isset($_POST['genre_id']) && ctype_digit((string) $_POST['genre_id'])
        ? (int) $_POST['genre_id']
        : 0;
    $name = trim((string) ($_POST['name'] ?? ''));

    try {
        if ($action === 'create') {
            if ($name === '') {
                throw new RuntimeException('Tên thể loại không được để trống.');
            }
            if (mb_strlen($name) > 50) {
                throw new RuntimeException('Tên thể loại tối đa 50 ký tự.');
            }

            db_insert('INSERT INTO genres (name) VALUES (?)', [$name]);
            flash('Đã thêm thể loại thành công.');
        } elseif ($action === 'update') {
            if ($genreId <= 0 || $name === '') {
                throw new RuntimeException('Dữ liệu thể loại không hợp lệ.');
            }
            if (mb_strlen($name) > 50) {
                throw new RuntimeException('Tên thể loại tối đa 50 ký tự.');
            }

            $exists = db_select_one('SELECT id FROM genres WHERE id = ?', [$genreId]);
            if (!$exists) {
                throw new RuntimeException('Thể loại không tồn tại.');
            }

            db_execute('UPDATE genres SET name = ? WHERE id = ?', [$name, $genreId]);
            flash('Đã cập nhật thể loại thành công.');
        } elseif ($action === 'delete') {
            if ($genreId <= 0) {
                throw new RuntimeException('Thể loại không hợp lệ.');
            }

            $genre = db_select_one('SELECT id, name FROM genres WHERE id = ?', [$genreId]);
            if (!$genre) {
                throw new RuntimeException('Thể loại không tồn tại.');
            }

            
            db_execute('DELETE FROM genres WHERE id = ?', [$genreId]);
            flash('Đã xóa thể loại "' . $genre['name'] . '".');
        } else {
            throw new RuntimeException('Thao tác không hợp lệ.');
        }
    } catch (PDOException $exception) {
       
        flash('Không thể lưu thể loại. Tên thể loại có thể đã tồn tại.', 'error');
    } catch (Throwable $exception) {
        flash($exception->getMessage(), 'error');
    }

    header('Location: ' . base_url('admin/genres.php'));
    exit;
}

if (isset($_GET['edit']) && ctype_digit((string) $_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $editingGenre = db_select_one('SELECT id, name FROM genres WHERE id = ?', [$editId]);
}

$genres = db_select(
    'SELECT g.id, g.name, COUNT(mg.movie_id) AS movie_count
     FROM genres g
     LEFT JOIN movie_genre mg ON mg.genre_id = g.id
     GROUP BY g.id, g.name
     ORDER BY g.name ASC'
);

require_once '../includes/header.php';
?>
<div class="container admin-container" style="max-width: 1000px; margin: 30px auto; padding: 0 15px;">
    <div style="margin-bottom: 24px;">
        <h2 style="margin-bottom: 6px;">Quản lý thể loại</h2>
        <p style="margin: 0; color: #8c93a8; font-size: 14px;">Thêm, sửa hoặc xóa thể loại phim trong hệ thống.</p>
    </div>

    <section class="card" style="margin-bottom: 24px;">
        <h3><?php echo $editingGenre ? 'Sửa thể loại' : 'Thêm thể loại mới'; ?></h3>
        <form method="POST" style="display: flex; gap: 12px; align-items: end; flex-wrap: wrap; margin-top: 14px;">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="<?php echo $editingGenre ? 'update' : 'create'; ?>">
            <?php if ($editingGenre): ?>
                <input type="hidden" name="genre_id" value="<?php echo (int) $editingGenre['id']; ?>">
            <?php endif; ?>

            <label style="display: grid; gap: 7px; flex: 1 1 320px;">
                <span>Tên thể loại</span>
                <input
                    type="text"
                    name="name"
                    maxlength="50"
                    required
                    value="<?php echo e($editingGenre['name'] ?? ''); ?>"
                    placeholder="Ví dụ: Phim Phiêu Lưu"
                >
            </label>

            <button type="submit">
                <?php echo $editingGenre ? 'Lưu thay đổi' : 'Thêm thể loại'; ?>
            </button>

            <?php if ($editingGenre): ?>
                <a href="genres.php" class="button button-ghost">Hủy</a>
            <?php endif; ?>
        </form>
    </section>

    <section style="overflow-x: auto; background: #12151e; border-radius: 8px; border: 1px solid #232733;">
        <table class="table-admin" style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="border-bottom: 2px solid #2a2e3d; color: #8c93a8; font-size: 14px;">
                    <th style="padding: 14px 16px; width: 80px;">ID</th>
                    <th style="padding: 14px 16px;">Tên thể loại</th>
                    <th style="padding: 14px 16px; width: 150px;">Số phim</th>
                    <th style="padding: 14px 16px; width: 180px; text-align: center;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($genres)): ?>
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 30px; color: #777;">Chưa có thể loại nào.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($genres as $genre): ?>
                        <tr style="border-bottom: 1px solid #1c202d;">
                            <td style="padding: 12px 16px; color: #a0a6b5;">#<?php echo (int) $genre['id']; ?></td>
                            <td style="padding: 12px 16px; color: #fff; font-weight: 600;"><?php echo e($genre['name']); ?></td>
                            <td style="padding: 12px 16px; color: #a0a6b5;"><?php echo (int) $genre['movie_count']; ?></td>
                            <td style="padding: 12px 16px; text-align: center; white-space: nowrap;">
                                <a href="genres.php?edit=<?php echo (int) $genre['id']; ?>" style="color: #3b82f6; text-decoration: none; font-weight: 500;">Sửa</a>
                                <span style="color: #333; margin: 0 8px;">|</span>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Xóa thể loại <?php echo e(addslashes($genre['name'])); ?>? Các liên kết thể loại của phim sẽ được xóa, nhưng phim không bị xóa.');">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="genre_id" value="<?php echo (int) $genre['id']; ?>">
                                    <button type="submit" style="padding: 0; border: 0; background: transparent; color: #ef4444; cursor: pointer; font: inherit; font-weight: 500;">Xóa</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </section>
</div>
<?php require_once '../includes/footer.php'; ?>
