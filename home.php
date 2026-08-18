<?php
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

<?php
$latestTab = $_GET['latest'] ?? 'all';
$latestTabs = ['all', 'phim_le_moi', 'phim_chieu_rap', 'phim_hoat_hinh'];
if (!in_array($latestTab, $latestTabs, true)) $latestTab = 'all';
$latestLabels = [
    'all' => 'Tất cả',
    'phim_le_moi' => 'Phim lẻ mới',
    'phim_chieu_rap' => 'Phim chiếu rạp',
    'phim_hoat_hinh' => 'Phim hoạt hình',
];
$latestMovies = get_home_latest_movies($latestTab, 8);
?>
<section class="container section-block" id="latest-movies">
    <?php
    $latestTabItems = [];
    foreach ($latestLabels as $key => $text) {
        $latestTabItems[] = [
            'text' => $text,
            'href' => base_url('home') . '?latest=' . urlencode($key) . '#latest-movies',
            'active' => $key === $latestTab,
        ];
    }
    render_section_tabs('MỚI CẬP NHẬT', $latestTabItems);
    render_movie_grid($latestMovies);
    ?>
</section>

<?php
$recommendationTab = $_GET['recommend'] ?? 'today';
$recommendationTabs = ['today', 'season', 'favorite', 'month'];
if (!in_array($recommendationTab, $recommendationTabs, true)) $recommendationTab = 'today';
$recommendationLabels = [
    'today' => 'Xem nhiều hôm nay',
    'season' => 'Xem nhiều trong mùa',
    'favorite' => 'Số lượt yêu thích',
    'month' => 'Tháng',
];
$recommendedMovies = get_home_recommendation_movies($recommendationTab, 20);
?>
<section class="container section-block" id="recommendations">
    <?php
    $recommendationTabItems = [];
    foreach ($recommendationLabels as $key => $text) {
        $recommendationTabItems[] = [
            'text' => $text,
            'href' => base_url('home') . '?recommend=' . urlencode($key) . '#recommendations',
            'active' => $key === $recommendationTab,
        ];
    }
    render_section_tabs('ĐỀ CỬ', $recommendationTabItems);
    ?>
    <?php if ($recommendationTab === 'favorite'): ?>
        <p class="recommendation-note">Xếp hạng theo lượt yêu thích ảo và điểm yêu thích 0–10 · có cộng thêm lượt lưu thực tế của người dùng.</p>
    <?php endif; ?>
    <?php render_movie_grid($recommendedMovies); ?>
</section>

<?php
$tamDiemTab = $_GET['td'] ?? 'soi_noi';
$tamDiemTabs = ['soi_noi', 'thanh_vien_tich_cuc', 'duoc_yeu_thich', 'binh_luan_nhieu_nhat'];
if (!in_array($tamDiemTab, $tamDiemTabs, true)) $tamDiemTab = 'soi_noi';

$tamDiemLabels = [
    'soi_noi'               => 'Sôi nổi',
    'thanh_vien_tich_cuc'   => 'Thành viên tích cực',
    'duoc_yeu_thich'        => 'Được yêu thích',
    'binh_luan_nhieu_nhat'  => 'Bình luận nhiều nhất',
];
?>
<section id="tam-diem" class="container section-block">
    <?php
    $tamDiemTabItems = [];
    foreach ($tamDiemLabels as $key => $text) {
        $tamDiemTabItems[] = [
            'text' => $text,
            'href' => e(base_url('home') . '?td=' . $key . '#tam-diem'),
            'active' => $key === $tamDiemTab,
        ];
    }
    render_section_tabs('TÂM ĐIỂM', $tamDiemTabItems);
    ?>

    <?php if ($tamDiemTab === 'soi_noi'): ?>
        <?php render_movie_grid(get_trending_movies_by_comments(10, 7)); ?>

    <?php elseif ($tamDiemTab === 'binh_luan_nhieu_nhat'): ?>
        <?php render_movie_grid(get_movies_by_comment_count(10)); ?>

    <?php elseif ($tamDiemTab === 'thanh_vien_tich_cuc'): ?>
        <?php $activeUsers = get_top_active_users(10); ?>
        <?php if (!$activeUsers): ?>
            <div class="empty-state">Chưa có dữ liệu.</div>
        <?php else: ?>
            <div class="ranked-list">
                <?php foreach ($activeUsers as $i => $u): ?>
                    <div class="ranked-list__row">
                        <span class="ranked-list__rank"><?php echo $i + 1; ?></span>
                        <div class="ranked-list__avatar" aria-hidden="true"><?php echo e(mb_strtoupper(mb_substr($u['username'], 0, 1))); ?></div>
                        <div class="ranked-list__content"><strong><?php echo e($u['username']); ?></strong></div>
                        <span class="ranked-list__stat">💬 <?php echo (int) $u['comment_count']; ?> bình luận</span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <?php elseif ($tamDiemTab === 'duoc_yeu_thich'): ?>
        <?php $likedUsers = get_top_liked_users(10); ?>
        <?php if (!$likedUsers): ?>
            <div class="empty-state">Chưa có dữ liệu.</div>
        <?php else: ?>
            <div class="ranked-list">
                <?php foreach ($likedUsers as $i => $u): ?>
                    <div class="ranked-list__row">
                        <span class="ranked-list__rank"><?php echo $i + 1; ?></span>
                        <div class="ranked-list__avatar" aria-hidden="true"><?php echo e(mb_strtoupper(mb_substr($u['username'], 0, 1))); ?></div>
                        <div class="ranked-list__content"><strong><?php echo e($u['username']); ?></strong></div>
                        <span class="ranked-list__stat">👍 <?php echo (int) $u['total_likes']; ?> lượt thích</span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>