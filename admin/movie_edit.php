<?php
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_admin();

$movieId = (int) ($_GET['id'] ?? 0);
if ($movieId <= 0) {
    header('Location: movies.php');
    exit;
}

$movie = db_select_one('SELECT * FROM movies WHERE id = ?', [$movieId]);
if (!$movie) {
    flash('Phim không tồn tại.', 'error');
    header('Location: movies.php');
    exit;
}

// Lấy danh sách ID thể loại hiện tại của phim
$stmtGenres = db()->prepare('SELECT genre_id FROM movie_genre WHERE movie_id = ?');
$stmtGenres->execute([$movieId]);
$currentGenreIds = $stmtGenres->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();

    $title = trim((string) ($_POST['title'] ?? ''));
    $slug = trim((string) ($_POST['slug'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $trailerUrl = trim((string) ($_POST['trailer_url'] ?? ''));
    $year = ($_POST['year'] ?? '') !== '' ? (int) $_POST['year'] : null;
    $rating = ($_POST['rating'] ?? '') !== '' ? (float) $_POST['rating'] : null;
    $genreIds = array_values(array_unique(array_map('intval', $_POST['genres'] ?? [])));

    if ($title === '' || $slug === '') {
        flash('Tiêu đề và slug là bắt buộc.', 'error');
    } else {
        try {
            $posterPath = $movie['poster_path'];

            // Nếu người dùng chọn tải ảnh mới
            if (isset($_FILES['poster_file']) && $_FILES['poster_file']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../assets/images/posters/';
                $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9\._-]/', '', basename($_FILES['poster_file']['name']));
                $targetPath = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['poster_file']['tmp_name'], $targetPath)) {
                    // Xóa file ảnh cũ
                    if (!empty($posterPath) && file_exists($uploadDir . $posterPath)) {
                        unlink($uploadDir . $posterPath);
                    }
                    $posterPath = $fileName;
                }
            }

            $pdo = db();
            $pdo->beginTransaction();

            db_execute(
                'UPDATE movies SET title = ?, slug = ?, description = ?, trailer_url = ?, release_year = ?, rating = ?, poster_path = ? WHERE id = ?',
                [$title, $slug, $description, $trailerUrl, $year, $rating, $posterPath, $movieId]
            );

            // Cập nhật lại thể loại
            db_execute('DELETE FROM movie_genre WHERE movie_id = ?', [$movieId]);
            foreach ($genreIds as $genreId) {
                db_execute('INSERT IGNORE INTO movie_genre (movie_id, genre_id) SELECT ?, id FROM genres WHERE id = ?', [$movieId, $genreId]);
            }

            $pdo->commit();
            flash('Cập nhật phim thành công!');
            header('Location: movies.php');
            exit;
        } catch (Throwable $e) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            flash('Lỗi khi cập nhật phim: ' . $e->getMessage(), 'error');
        }
    }
}

$genres = get_all_genres();
require_once '../includes/header.php';
?>
<div class="container">
    <h2>Chỉnh sửa phim</h2>

    <form method="POST" class="card admin-movie-form" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>

        <label>
            Tiêu đề
            <input type="text" name="title" required value="<?php echo e($_POST['title'] ?? $movie['title']); ?>">
        </label>

        <label>
            Slug
            <input type="text" name="slug" required value="<?php echo e($_POST['slug'] ?? $movie['slug']); ?>">
        </label>

        <label>
            Ảnh Poster hiện tại
            <?php if (!empty($movie['poster_path'])): ?>
                <div style="margin: 8px 0;">
                    <img src="../assets/images/posters/<?php echo e($movie['poster_path']); ?>" style="width: 70px; border-radius: 4px;">
                </div>
            <?php endif; ?>
            <input type="file" name="poster_file" accept="image/*">
        </label>

        <label>
            Link Trailer YouTube (hoặc Video ID)
            <input type="text" name="trailer_url" placeholder="https://www.youtube.com/watch?v=... hoặc mã Video ID" value="<?php echo e($_POST['trailer_url'] ?? $movie['trailer_url'] ?? ''); ?>">
        </label>

        <label>
            Mô tả
            <textarea id="movie-editor" name="description"><?php echo e($_POST['description'] ?? $movie['description']); ?></textarea>
        </label>

        <div class="admin-form-row">
            <label>
                Năm phát hành
                <input type="number" name="year" min="1888" max="2100" value="<?php echo e((string)($_POST['year'] ?? $movie['release_year'])); ?>">
            </label>
            <label>
                Điểm đánh giá
                <input type="number" step="0.1" min="0" max="10" name="rating" value="<?php echo e((string)($_POST['rating'] ?? $movie['rating'])); ?>">
            </label>
        </div>

        <fieldset>
            <legend>Thể loại phim</legend>
            <div class="genre-checkbox-grid">
                <?php 
                $selectedGenres = isset($_POST['genres']) ? array_map('intval', $_POST['genres']) : $currentGenreIds; 
                ?>
                <?php foreach ($genres as $genre): ?>
                    <label class="genre-checkbox">
                        <input
                            type="checkbox"
                            name="genres[]"
                            value="<?php echo (int) $genre['id']; ?>"
                            <?php echo in_array((int) $genre['id'], $selectedGenres, true) ? 'checked' : ''; ?>
                        >
                        <span><?php echo e($genre['name']); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <button type="submit">Lưu thay đổi</button>
    </form>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js" referrerpolicy="no-referrer"></script>
<script>
  tinymce.init({
    selector: '#movie-editor',
    height: 280,
    menubar: false,
    plugins: 'lists link code visualblocks wordcount',
    toolbar: 'undo redo | bold italic underline | bullist numlist | alignleft aligncenter alignright | removeformat | code',
    skin: 'oxide-dark',
    content_css: 'dark',
    setup: function (editor) {
      editor.on('change', function () {
        tinymce.triggerSave();
      });
    }
  });
</script>

<?php require_once '../includes/footer.php'; ?>