<?php
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();

    $title = trim((string) ($_POST['title'] ?? ''));
    $slug = trim((string) ($_POST['slug'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $year = ($_POST['year'] ?? '') !== '' ? (int) $_POST['year'] : null;
    $rating = ($_POST['rating'] ?? '') !== '' ? (float) $_POST['rating'] : null;
    $genreIds = array_values(array_unique(array_map('intval', $_POST['genres'] ?? [])));

    if ($title === '' || $slug === '') {
        flash('Tiêu đề và slug là bắt buộc.', 'error');
    } else {
        try {
            // 1. XỬ LÝ UPLOAD ẢNH POSTER
            $posterPath = null;
            if (isset($_FILES['poster_file']) && $_FILES['poster_file']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../assets/images/posters/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9\._-]/', '', basename($_FILES['poster_file']['name']));
                $targetPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['poster_file']['tmp_name'], $targetPath)) {
                    $posterPath = $fileName;
                }
            }

            $pdo = db();
            $pdo->beginTransaction();

            // 2. LƯU THÔNG TIN PHIM
            $movieId = (int) db_insert(
                'INSERT INTO movies (title, slug, description, release_year, rating, poster_path) VALUES (?, ?, ?, ?, ?, ?)',
                [$title, $slug, $description, $year, $rating, $posterPath]
            );

            // Liên kết thể loại
            foreach ($genreIds as $genreId) {
                db_execute(
                    'INSERT IGNORE INTO movie_genre (movie_id, genre_id)
                     SELECT ?, id FROM genres WHERE id = ?',
                    [$movieId, $genreId]
                );
            }

            $pdo->commit();
            flash('Đã thêm phim và thể loại thành công!');
            header('Location: movies.php');
            exit;
        } catch (Throwable $exception) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            flash('Không thể lưu phim. Slug có thể đã tồn tại.', 'error');
        }
    }
}

$genres = get_all_genres();
require_once '../includes/header.php';
?>
<div class="container">
    <h2>Thêm phim</h2>

    <form method="POST" class="card admin-movie-form" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>

        <label>
            Tiêu đề
            <input type="text" name="title" placeholder="Ví dụ: Inception" required value="<?php echo e($_POST['title'] ?? ''); ?>">
        </label>

        <label>
            Slug
            <input type="text" name="slug" placeholder="vi-du-inception" required value="<?php echo e($_POST['slug'] ?? ''); ?>">
        </label>

        <label>
            Ảnh Poster (Upload)
            <input type="file" name="poster_file" accept="image/*">
        </label>

        <label>
            Mô tả
            <!-- Đặt id để TinyMCE nhận diện -->
            <textarea id="movie-editor" name="description" placeholder="Mô tả nội dung phim"><?php echo e($_POST['description'] ?? ''); ?></textarea>
        </label>

        <div class="admin-form-row">
            <label>
                Năm phát hành
                <input type="number" name="year" min="1888" max="2100" placeholder="2026" value="<?php echo e($_POST['year'] ?? ''); ?>">
            </label>
            <label>
                Điểm đánh giá
                <input type="number" step="0.1" min="0" max="10" name="rating" placeholder="8.5" value="<?php echo e($_POST['rating'] ?? ''); ?>">
            </label>
        </div>

        <fieldset>
            <legend>Thể loại phim</legend>
            <div class="genre-checkbox-grid">
                <?php $selectedGenres = array_map('intval', $_POST['genres'] ?? []); ?>
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

        <button type="submit">Thêm phim</button>
    </form>
</div>

<!-- TÍCH HỢP TINYMCE (RICH TEXT EDITOR) TƯƠNG THÍCH GIAO DIỆN TỐI -->
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