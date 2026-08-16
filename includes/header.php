<?php 
require_once __DIR__ . '/functions.php';

// Xử lý SEO cơ bản
$seoTitle = isset($movie['title']) 
    ? e($movie['title']) . ' - Movie Hub' 
    : 'Movie Hub - Khám phá điện ảnh';

$seoDesc = isset($movie['description']) 
    ? mb_strimwidth(strip_tags($movie['description']), 0, 160, '...') 
    : 'Khám phá hàng ngàn bộ phim hấp dẫn, lưu danh sách yêu thích và nhận gợi ý theo tâm trạng tại Movie Hub.';

$currentUser = current_user();
$isAdmin = $currentUser && ($currentUser['role'] ?? '') === 'admin';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $seoTitle; ?></title>
    <meta name="description" content="<?php echo e($seoDesc); ?>">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="<?php echo base_url('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/responsive.css'); ?>">
    
    <?php if (str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/admin/')): ?>
        <link rel="stylesheet" href="<?php echo base_url('assets/css/admin-style.css'); ?>">
    <?php endif; ?>
</head>
<body>
    <header class="site-header">
        <div class="header-inner">
            <a class="brand" href="<?php echo base_url('home'); ?>">Movie Hub</a>

            <!-- Nút mở drawer menu trên mobile -->
            <button id="menu-toggle" class="menu-toggle" type="button" aria-label="Mở menu" aria-controls="primary-navigation" aria-expanded="false">
                <span></span><span></span><span></span>
            </button>

            <nav id="primary-navigation" class="nav-menu" aria-label="Điều hướng chính">
                <a href="<?php echo base_url('home'); ?>">Trang chủ</a>
                <a href="<?php echo base_url('browse'); ?>">Khám phá</a>
                
                <?php if ($currentUser): ?>
                    <?php if ($isAdmin): ?>
                        <a href="<?php echo base_url('admin/index.php'); ?>" class="nav-link-admin">Quản trị Admin</a>
                    <?php endif; ?>
                    
                    <a href="<?php echo base_url('profile'); ?>">Hồ sơ</a>
                    <a href="<?php echo base_url('watchlist'); ?>">Danh sách xem</a>
                    <a href="<?php echo base_url('logout'); ?>" onclick="return confirm('Bạn có chắc chắn muốn đăng xuất không?');">Đăng xuất</a>
                <?php else: ?>
                    <a href="<?php echo base_url('login'); ?>" class="btn-login-gold">ĐĂNG NHẬP</a>
                <?php endif; ?>
            </nav>

            <!-- Live Search -->
            <div class="live-search">
                <label class="sr-only" for="live-search-input">Tìm phim</label>
                <input id="live-search-input" type="search" placeholder="Tìm phim..." autocomplete="off">
                <div id="live-search-dropdown" class="live-search-dropdown" role="listbox" aria-label="Gợi ý phim"></div>
            </div>
        </div>
    </header>

    <div id="drawer-overlay" class="drawer-overlay" aria-hidden="true"></div>
    <main>
        <?php render_flashes(); ?> 
