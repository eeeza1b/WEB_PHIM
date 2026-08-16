<?php
require_login();

$user = current_user();
$userId = (int)$user['id'];

// Lấy thông tin user mới nhất từ CSDL
$userData = db_select_one("SELECT * FROM users WHERE id = ?", [$userId]);

// Đếm tổng số phim trong danh sách xem
$watchlistStat = db_select_one("SELECT COUNT(*) AS total FROM watchlist WHERE user_id = ?", [$userId]);
$totalWatchlist = (int)($watchlistStat['total'] ?? 0);

$errorMessage = '';
$successMessage = '';

// Xử lý đổi mật khẩu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    csrf_validate();

    $currentPass = $_POST['current_password'] ?? '';
    $newPass     = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    if (empty($currentPass) || empty($newPass) || empty($confirmPass)) {
        $errorMessage = 'Vui lòng điền đầy đủ các trường mật khẩu!';
    } elseif (!password_verify($currentPass, $userData['password'])) {
        $errorMessage = 'Mật khẩu hiện tại không chính xác!';
    } elseif (strlen($newPass) < 6) {
        $errorMessage = 'Mật khẩu mới phải có ít nhất 6 ký tự!';
    } elseif ($newPass !== $confirmPass) {
        $errorMessage = 'Mật khẩu xác nhận không khớp!';
    } else {
        $newHash = password_hash($newPass, PASSWORD_BCRYPT);
        db_execute("UPDATE users SET password = ? WHERE id = ?", [$newHash, $userId]);
        $successMessage = 'Đổi mật khẩu thành công!';
        // Cập nhật lại hash trong biến userData
        $userData['password'] = $newHash;
    }
}
?>

<div style="max-width: 1000px; margin: 40px auto; padding: 0 15px;">
    
    <div style="margin-bottom: 30px;">
        <h2 style="color: #fff; font-size: 26px; font-weight: 700; margin: 0 0 6px 0;">Hồ sơ cá nhân</h2>
        <p style="color: #8c8f96; font-size: 14px; margin: 0;">Quản lý thông tin tài khoản và bảo mật của bạn.</p>
    </div>

    <?php if (!empty($errorMessage)): ?>
        <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #fca5a5; padding: 12px 16px; border-radius: 8px; font-size: 14px; margin-bottom: 24px;">
            ✕ <?php echo e($errorMessage); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($successMessage)): ?>
        <div style="background: rgba(34, 197, 94, 0.15); border: 1px solid #22c55e; color: #86efac; padding: 12px 16px; border-radius: 8px; font-size: 14px; margin-bottom: 24px;">
            ✓ <?php echo e($successMessage); ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 1.3fr; gap: 24px;">
        
        <!-- Cột trái: Thông tin tài khoản & Thống kê -->
        <div style="display: flex; flex-direction: column; gap: 20px;">
            
            <!-- Card User Info -->
            <div style="background: #18191c; border: 1px solid #2a2b2f; border-radius: 14px; padding: 28px; text-align: center;">
                <div style="width: 76px; height: 76px; margin: 0 auto 16px auto; background: #eab308; color: #000; font-size: 32px; font-weight: 700; border-radius: 50%; display: flex; align-items: center; justify-content: center; text-transform: uppercase;">
                    <?php echo substr($userData['username'] ?? 'U', 0, 1); ?>
                </div>
                
                <h3 style="color: #fff; margin: 0 0 4px 0; font-size: 20px;"><?php echo e($userData['username'] ?? ''); ?></h3>
                <p style="color: #8c8f96; font-size: 13.5px; margin: 0 0 16px 0;"><?php echo e($userData['email'] ?? ''); ?></p>
                
                <span style="display: inline-block; padding: 4px 14px; border-radius: 20px; font-size: 12px; font-weight: 600; background: <?php echo ($userData['role'] ?? '') === 'admin' ? 'rgba(234, 179, 8, 0.15)' : 'rgba(59, 130, 246, 0.15)'; ?>; color: <?php echo ($userData['role'] ?? '') === 'admin' ? '#eab308' : '#60a5fa'; ?>; border: 1px solid <?php echo ($userData['role'] ?? '') === 'admin' ? '#eab308' : '#3b82f6'; ?>;">
                    <?php echo strtoupper($userData['role'] ?? 'USER'); ?>
                </span>
            </div>

            <!-- Card Watchlist Count -->
            <div style="background: #18191c; border: 1px solid #2a2b2f; border-radius: 14px; padding: 22px 24px; display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <div style="color: #8c8f96; font-size: 13px; margin-bottom: 4px;">Phim đang lưu</div>
                    <div style="color: #eab308; font-size: 26px; font-weight: 700;"><?php echo $totalWatchlist; ?></div>
                </div>
                <a href="<?php echo e(base_url('watchlist')); ?>" style="padding: 8px 14px; background: #222328; border: 1px solid #33363d; color: #fff; text-decoration: none; border-radius: 8px; font-size: 13px; font-weight: 600; transition: 0.2s;" onmouseover="this.style.borderColor='#eab308';" onmouseout="this.style.borderColor='#33363d';">
                    Xem danh sách →
                </a>
            </div>

        </div>

        <!-- Cột phải: Form Đổi mật khẩu -->
        <div style="background: #18191c; border: 1px solid #2a2b2f; border-radius: 14px; padding: 28px;">
            <h3 style="color: #fff; font-size: 18px; margin: 0 0 6px 0;">Bảo mật & Đổi mật khẩu</h3>
            <p style="color: #8c8f96; font-size: 13px; margin: 0 0 20px 0;">Nên sử dụng mật khẩu mạnh có ít nhất 6 ký tự.</p>

            <form action="<?php echo e(base_url('profile')); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="change_password">

                <div style="margin-bottom: 14px;">
                    <label style="display: block; color: #d1d5db; font-size: 13px; margin-bottom: 6px;">Mật khẩu hiện tại</label>
                    <input type="password" name="current_password" required placeholder="••••••••" style="width: 100%; box-sizing: border-box; padding: 11px 14px; background: #222328; border: 1px solid #2e3036; border-radius: 8px; color: #fff; font-size: 14px; outline: none;" onfocus="this.style.borderColor='#eab308';" onblur="this.style.borderColor='#2e3036';">
                </div>

                <div style="margin-bottom: 14px;">
                    <label style="display: block; color: #d1d5db; font-size: 13px; margin-bottom: 6px;">Mật khẩu mới</label>
                    <input type="password" name="new_password" required placeholder="Tối thiểu 6 ký tự" style="width: 100%; box-sizing: border-box; padding: 11px 14px; background: #222328; border: 1px solid #2e3036; border-radius: 8px; color: #fff; font-size: 14px; outline: none;" onfocus="this.style.borderColor='#eab308';" onblur="this.style.borderColor='#2e3036';">
                </div>

                <div style="margin-bottom: 22px;">
                    <label style="display: block; color: #d1d5db; font-size: 13px; margin-bottom: 6px;">Xác nhận mật khẩu mới</label>
                    <input type="password" name="confirm_password" required placeholder="••••••••" style="width: 100%; box-sizing: border-box; padding: 11px 14px; background: #222328; border: 1px solid #2e3036; border-radius: 8px; color: #fff; font-size: 14px; outline: none;" onfocus="this.style.borderColor='#eab308';" onblur="this.style.borderColor='#2e3036';">
                </div>

                <button type="submit" style="padding: 11px 22px; background: #eab308; border: none; border-radius: 8px; color: #000; font-size: 14px; font-weight: 700; cursor: pointer; transition: 0.2s;" onmouseover="this.style.background='#ca8a04';" onmouseout="this.style.background='#eab308';">
                    Cập nhật mật khẩu
                </button>
            </form>
        </div>

    </div>
</div>