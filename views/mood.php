<?php
// Bản đồ ánh xạ tâm trạng -> thể loại tương ứng
$moodDictionary = [
    'happy' => [
        'id'     => 1,
        'title'  => 'Vui vẻ & Yêu đời',
        'icon'   => '😊',
        'desc'   => 'Những bộ phim hài hước, hoạt hình tươi sáng mang lại nhiều năng lượng tích cực.',
        'genres' => ['Hài', 'Hài Hước', 'Hoạt Hình', 'Gia Đình', 'Comedy', 'Animation', 'Family']
    ],
    'relaxing' => [
        'id'     => 2,
        'title'  => 'Thư giãn & Nhẹ nhàng',
        'icon'   => '🌿',
        'desc'   => 'Những thước phim êm dịu, phong cảnh tuyệt đẹp giúp bạn thả lỏng tâm trí.',
        'genres' => ['Phiêu Lưu', 'Tài Liệu', 'Âm Nhạc', 'Adventure', 'Documentary', 'Music']
    ],
    'romantic' => [
        'id'     => 3,
        'title'  => 'Lãng mạn & Ngọt ngào',
        'icon'   => '💖',
        'desc'   => 'Những câu chuyện tình yêu say đắm, sâu sắc chạm đến từng cung bậc cảm xúc.',
        'genres' => ['Lãng Mạn', 'Tình Cảm', 'Tâm Lý', 'Romance', 'Drama']
    ],
    'sad' => [
        'id'     => 4,
        'title'  => 'Trầm lắng & Chiêm nghiệm',
        'icon'   => '🌧️',
        'desc'   => 'Những bộ phim giàu tính nhân văn, sâu lắng và nhiều khoảng lặng.',
        'genres' => ['Chính Kịch', 'Tâm Lý', 'Drama']
    ],
    'thrilling' => [
        'id'     => 5,
        'title'  => 'Kịch tính & Hồi hộp',
        'icon'   => '⚡',
        'desc'   => 'Những pha hành động dồn dập, giật gân, bí ẩn khiến bạn không thể rời mắt.',
        'genres' => ['Hành Động', 'Kinh Dị', 'Giật Gân', 'Bí Ẩn', 'Khoa Học Viễn Tưởng', 'Action', 'Horror', 'Thriller', 'Mystery', 'Sci-Fi']
    ]
];

// Xử lý khi nhận POST từ form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mood_id'])) {
    csrf_validate();
    $moodId = (int)$_POST['mood_id'];
    
    if (function_exists('save_mood_cookie')) {
        save_mood_cookie($moodId);
    }
    
    if (current_user()) {
        db_execute("INSERT INTO user_mood_history (user_id, mood_id) VALUES (?, ?)", [current_user()['id'], $moodId]);
    }
    
    // Tìm slug tương ứng theo id
    foreach ($moodDictionary as $slug => $data) {
        if ($data['id'] === $moodId) {
            header('Location: ' . base_url('mood') . '?mood=' . $slug);
            exit;
        }
    }
}

// Nhận tham số từ GET
$moodParam = strtolower(trim($_GET['mood'] ?? 'happy'));
if (!array_key_exists($moodParam, $moodDictionary)) {
    $moodParam = 'happy';
}

$activeMood = $moodDictionary[$moodParam];
$genreList = $activeMood['genres'];

// Lưu cookie & lịch sử khi vào bằng GET
if (function_exists('save_mood_cookie')) {
    save_mood_cookie($activeMood['id']);
}
if (current_user()) {
    db_execute("INSERT INTO user_mood_history (user_id, mood_id) VALUES (?, ?)", [current_user()['id'], $activeMood['id']]);
}

// Tạo câu lệnh SQL lọc phim theo thể loại
$placeholders = implode(',', array_fill(0, count($genreList), '?'));
$sql = "
    SELECT m.*, GROUP_CONCAT(DISTINCT g.name SEPARATOR ', ') AS genre_names
    FROM movies m
    JOIN movie_genre mg ON m.id = mg.movie_id
    JOIN genres g ON mg.genre_id = g.id
    WHERE m.id IN (
        SELECT DISTINCT mg2.movie_id
        FROM movie_genre mg2
        JOIN genres g2 ON mg2.genre_id = g2.id
        WHERE g2.name IN ($placeholders)
    )
    GROUP BY m.id
    ORDER BY m.rating DESC, m.id DESC
    LIMIT 20
";

$movies = db_select($sql, $genreList);

// Fallback nếu chưa có phim trùng thể loại
if (empty($movies)) {
    $movies = db_select("
        SELECT m.*, GROUP_CONCAT(DISTINCT g.name SEPARATOR ', ') AS genre_names
        FROM movies m
        LEFT JOIN movie_genre mg ON m.id = mg.movie_id
        LEFT JOIN genres g ON mg.genre_id = g.id
        GROUP BY m.id
        ORDER BY m.rating DESC
        LIMIT 12
    ");
}
?>

<div style="max-width: 1200px; margin: 35px auto; padding: 0 15px;">
    
    <!-- Thanh chọn nhanh tâm trạng -->
    <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; margin-bottom: 30px;">
        <?php foreach ($moodDictionary as $key => $mood): ?>
            <a href="<?php echo e(base_url('mood') . '?mood=' . $key); ?>" 
               style="padding: 9px 20px; border-radius: 25px; text-decoration: none; font-size: 14px; font-weight: 600; transition: 0.2s; <?php echo $key === $moodParam ? 'background: #eab308; color: #000;' : 'background: #18191c; border: 1px solid #2e3036; color: #9ca3af;'; ?>">
                <?php echo $mood['icon'] . ' ' . $mood['title']; ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Header giới thiệu tâm trạng -->
    <div style="background: #18191c; border: 1px solid #2a2b2f; border-radius: 16px; padding: 30px; margin-bottom: 35px;">
        <div style="font-size: 12.5px; font-weight: 700; color: #eab308; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">
            Gợi ý theo cảm xúc
        </div>
        <h2 style="color: #ffffff; font-size: 26px; margin: 0 0 10px 0;">
            <?php echo $activeMood['icon'] . ' ' . $activeMood['title']; ?>
        </h2>
        <p style="color: #9ca3af; font-size: 14px; margin: 0; max-width: 700px; line-height: 1.6;">
            <?php echo e($activeMood['desc']); ?>
        </p>
    </div>

    <!-- Danh sách phim -->
    <?php if (empty($movies)): ?>
        <div style="background: #18191c; border: 1px solid #2a2b2f; border-radius: 12px; text-align: center; padding: 50px 20px;">
            <p style="color: #8c8f96; font-size: 15px;">Chưa có phim phù hợp với tâm trạng này trong hệ thống.</p>
        </div>
    <?php else: ?>
        <div class="movie-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: 24px;">
            <?php foreach ($movies as $movie): ?>
                <div style="background: #18191c; border: 1px solid #2a2b2f; border-radius: 10px; overflow: hidden; display: flex; flex-direction: column; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-4px)';" onmouseout="this.style.transform='translateY(0)';">
                    <a href="<?php echo e(movie_detail_url((int)$movie['id'])); ?>">
                        <img 
                            src="<?php echo e(movie_poster_url($movie['poster_path'] ?? null, 'w342')); ?>" 
                            alt="Poster <?php echo e($movie['title']); ?>" 
                            style="width: 100%; height: 290px; object-fit: cover;"
                        >
                    </a>
                    
                    <div style="padding: 16px; flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <h4 style="margin: 0 0 6px 0; font-size: 15px; font-weight: 600;">
                                <a href="<?php echo e(movie_detail_url((int)$movie['id'])); ?>" style="color: #fff; text-decoration: none;">
                                    <?php echo e($movie['title']); ?>
                                </a>
                            </h4>
                            <div style="font-size: 12.5px; color: #8c8f96; margin-bottom: 10px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                <?php echo e($movie['genre_names'] ?? 'Chưa phân loại'); ?>
                            </div>
                        </div>

                        <div style="display: flex; align-items: center; justify-content: space-between; font-size: 13px;">
                            <span style="color: #eab308; font-weight: bold;">
                                ★ <?php echo e(number_format((float)($movie['rating'] ?? 0), 1)); ?>
                            </span>
                            <span style="color: #6b7280;">
                                <?php echo e((string)($movie['release_year'] ?? '')); ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>