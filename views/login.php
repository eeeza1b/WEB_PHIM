<?php
// Nếu đã đăng nhập thì tự động chuyển hướng về trang chủ
if (is_logged_in()) {
    header('Location: ' . base_url('index.php'));
    exit;
}

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $user = attempt_login($username, $password);
    if ($user) {
        login_user($user);
        header('Location: ' . base_url('index.php'));
        exit;
    } else {
        $errorMessage = 'Tên người dùng hoặc mật khẩu không chính xác!';
    }
}
?>

<div style="min-height: 80vh; display: flex; align-items: center; justify-content: center; padding: 40px 15px;">
    <div style="width: 100%; max-width: 440px; background: #18191c; border: 1px solid #2a2b2f; border-radius: 16px; padding: 40px 32px; box-shadow: 0 10px 30px rgba(0,0,0,0.6);">
        
        <!-- Tiêu đề -->
        <div style="text-align: center; margin-bottom: 28px;">
            <h2 style="color: #ffffff; font-size: 26px; font-weight: 700; margin: 0 0 10px 0;">Chào mừng trở lại!</h2>
            <p style="color: #8c8f96; font-size: 13.5px; margin: 0; line-height: 1.5;">Đăng nhập để trải nghiệm không gian điện ảnh riêng của bạn.</p>
        </div>

        <?php if (!empty($errorMessage)): ?>
            <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #fca5a5; padding: 10px 14px; border-radius: 8px; font-size: 13.5px; margin-bottom: 20px; text-align: center;">
                <?php echo e($errorMessage); ?>
            </div>
        <?php endif; ?>

        <!-- Form đăng nhập -->
        <form action="<?php echo e(base_url('login')); ?>" method="POST" style="margin: 0;">
            <?php echo csrf_field(); ?>

            <!-- Tên người dùng -->
            <div style="position: relative; margin-bottom: 14px;">
                <span style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #6b7280; font-size: 15px;">✉</span>
                <input 
                    type="text" 
                    name="username" 
                    required 
                    placeholder="Tên người dùng"
                    style="width: 100%; box-sizing: border-box; padding: 12px 14px 12px 42px; background: #222328; border: 1px solid #2e3036; border-radius: 8px; color: #fff; font-size: 14px; outline: none; transition: 0.2s;"
                    onfocus="this.style.borderColor='#eab308';"
                    onblur="this.style.borderColor='#2e3036';"
                >
            </div>

            <!-- Mật khẩu -->
            <div style="position: relative; margin-bottom: 16px;">
                <span style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #6b7280; font-size: 15px;">🔒</span>
                <input 
                    type="password" 
                    name="password" 
                    required 
                    placeholder="Mật khẩu"
                    style="width: 100%; box-sizing: border-box; padding: 12px 14px 12px 42px; background: #222328; border: 1px solid #2e3036; border-radius: 8px; color: #fff; font-size: 14px; outline: none; transition: 0.2s;"
                    onfocus="this.style.borderColor='#eab308';"
                    onblur="this.style.borderColor='#2e3036';"
                >
            </div>

            <!-- Ghi nhớ & Quên mật khẩu -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px; font-size: 13px;">
                <label style="color: #9ca3af; display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="remember" style="accent-color: #eab308; cursor: pointer;"> Ghi nhớ tôi
                </label>
                <a href="#" style="color: #9ca3af; text-decoration: none;" onmouseover="this.style.color='#fff';" onmouseout="this.style.color='#9ca3af';">Quên mật khẩu?</a>
            </div>

            <!-- Nút Đăng nhập -->
            <button 
                type="submit" 
                style="width: 100%; padding: 12px; background: #eab308; border: none; border-radius: 8px; color: #000; font-size: 15px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; transition: 0.2s;"
                onmouseover="this.style.background='#ca8a04';"
                onmouseout="this.style.background='#eab308';"
            >
                Đăng Nhập <span style="font-size: 17px;">→</span>
            </button>
        </form>

        <!-- Divider phân cách -->
        <div style="position: relative; text-align: center; margin: 24px 0 20px 0;">
            <div style="position: absolute; top: 50%; left: 0; right: 0; height: 1px; background: #2e3036;"></div>
            <span style="position: relative; background: #18191c; padding: 0 12px; color: #6b7280; font-size: 12px;">Hoặc đăng nhập với</span>
        </div>

        <!-- Nút Đăng nhập Google -->
        <a 
            href="#" 
            onclick="alert('Chức năng Google OAuth cần cấu hình API Key trên Google Cloud Console!'); return false;"
            style="display: flex; align-items: center; justify-content: center; gap: 10px; width: 100%; box-sizing: border-box; padding: 11px; background: #ffffff; border-radius: 8px; text-decoration: none; color: #1f2937; font-size: 14px; font-weight: 600; transition: 0.2s;"
            onmouseover="this.style.background='#f3f4f6';"
            onmouseout="this.style.background='#ffffff';"
        >
            <svg width="18" height="18" viewBox="0 0 48 48">
                <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
            </svg>
            Google
        </a>

        <!-- Chuyển hướng Đăng ký -->
        <div style="text-align: center; margin-top: 24px; font-size: 13.5px; color: #8c8f96;">
            Bạn mới đến Movie Hub? 
            <a href="<?php echo e(base_url('register')); ?>" style="color: #eab308; font-weight: 600; text-decoration: none;" onmouseover="this.style.textDecoration='underline';" onmouseout="this.style.textDecoration='none';">
                Đăng ký ngay
            </a>
        </div>

    </div>
</div>