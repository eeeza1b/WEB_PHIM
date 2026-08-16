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
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        $errorMessage = 'Vui lòng điền đầy đủ tất cả các trường!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Địa chỉ email không hợp lệ!';
    } elseif (strlen($password) < 6) {
        $errorMessage = 'Mật khẩu phải có ít nhất 6 ký tự!';
    } else {
        // Gọi hàm đăng ký có sẵn của hệ thống
        $registered = register_user($username, $email, $password);

        if ($registered) {
            flash('Đăng ký tài khoản thành công! Vui lòng đăng nhập.');
            header('Location: ' . base_url('login'));
            exit;
        } else {
            $errorMessage = 'Tên tài khoản hoặc email đã tồn tại!';
        }
    }
}
?>

<div style="min-height: 80vh; display: flex; align-items: center; justify-content: center; padding: 40px 15px;">
    <div style="width: 100%; max-width: 440px; background: #18191c; border: 1px solid #2a2b2f; border-radius: 16px; padding: 40px 32px; box-shadow: 0 10px 30px rgba(0,0,0,0.6);">
        
        <!-- Tiêu đề -->
        <div style="text-align: center; margin-bottom: 24px;">
            <h2 style="color: #ffffff; font-size: 26px; font-weight: 700; margin: 0 0 8px 0;">Đăng ký tài khoản</h2>
            <p style="color: #8c8f96; font-size: 13.5px; margin: 0;">Tham gia Movie Hub để tạo danh sách xem của riêng bạn.</p>
        </div>

        <?php if (!empty($errorMessage)): ?>
            <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #fca5a5; padding: 10px 14px; border-radius: 8px; font-size: 13.5px; margin-bottom: 18px; text-align: center;">
                <?php echo e($errorMessage); ?>
            </div>
        <?php endif; ?>

        <!-- Form Đăng ký -->
        <form action="<?php echo e(base_url('register')); ?>" method="POST" style="margin: 0;">
            <?php echo csrf_field(); ?>

            <!-- Username -->
            <div style="position: relative; margin-bottom: 14px;">
                <span style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #6b7280; font-size: 15px;">👤</span>
                <input 
                    type="text" 
                    name="username" 
                    required 
                    placeholder="Tên người dùng"
                    value="<?php echo e($_POST['username'] ?? ''); ?>"
                    style="width: 100%; box-sizing: border-box; padding: 12px 14px 12px 42px; background: #222328; border: 1px solid #2e3036; border-radius: 8px; color: #fff; font-size: 14px; outline: none; transition: 0.2s;"
                    onfocus="this.style.borderColor='#eab308';"
                    onblur="this.style.borderColor='#2e3036';"
                >
            </div>

            <!-- Email -->
            <div style="position: relative; margin-bottom: 14px;">
                <span style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #6b7280; font-size: 15px;">✉</span>
                <input 
                    type="email" 
                    name="email" 
                    required 
                    placeholder="Địa chỉ Email"
                    value="<?php echo e($_POST['email'] ?? ''); ?>"
                    style="width: 100%; box-sizing: border-box; padding: 12px 14px 12px 42px; background: #222328; border: 1px solid #2e3036; border-radius: 8px; color: #fff; font-size: 14px; outline: none; transition: 0.2s;"
                    onfocus="this.style.borderColor='#eab308';"
                    onblur="this.style.borderColor='#2e3036';"
                >
            </div>

            <!-- Password -->
            <div style="position: relative; margin-bottom: 22px;">
                <span style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #6b7280; font-size: 15px;">🔒</span>
                <input 
                    type="password" 
                    name="password" 
                    required 
                    placeholder="Mật khẩu (tối thiểu 6 ký tự)"
                    style="width: 100%; box-sizing: border-box; padding: 12px 14px 12px 42px; background: #222328; border: 1px solid #2e3036; border-radius: 8px; color: #fff; font-size: 14px; outline: none; transition: 0.2s;"
                    onfocus="this.style.borderColor='#eab308';"
                    onblur="this.style.borderColor='#2e3036';"
                >
            </div>

            <!-- Nút Tạo tài khoản -->
            <button 
                type="submit" 
                style="width: 100%; padding: 12px; background: #eab308; border: none; border-radius: 8px; color: #000; font-size: 15px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; transition: 0.2s;"
                onmouseover="this.style.background='#ca8a04';"
                onmouseout="this.style.background='#eab308';"
            >
                Tạo Tài Khoản <span style="font-size: 17px;">→</span>
            </button>
        </form>

        <!-- Chuyển hướng Đăng nhập -->
        <div style="text-align: center; margin-top: 24px; font-size: 13.5px; color: #8c8f96;">
            Đã có tài khoản? 
            <a href="<?php echo e(base_url('login')); ?>" style="color: #eab308; font-weight: 600; text-decoration: none;" onmouseover="this.style.textDecoration='underline';" onmouseout="this.style.textDecoration='none';">
                Đăng nhập ngay
            </a>
        </div>

    </div>
</div>