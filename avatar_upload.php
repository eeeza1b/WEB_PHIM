<?php
// JSON-only endpoint for profile avatar uploads.
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

try {
    require_login();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['action'] ?? '') !== 'upload_avatar') {
        http_response_code(405);
        throw new RuntimeException('Yêu cầu không hợp lệ.');
    }

    csrf_validate();

    $user = current_user();
    $userId = (int)$user['id'];
    $userData = db_select_one("SELECT * FROM users WHERE id = ?", [$userId]);
    if (!$userData) {
        throw new RuntimeException('Không tìm thấy tài khoản.');
    }

    // Tương thích database cũ chưa có cột avatar.
    try {
        $hasAvatarColumn = db_select_one(
            "SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'avatar'"
        );
        if ((int)($hasAvatarColumn['c'] ?? 0) === 0) {
            db()->exec("ALTER TABLE users ADD COLUMN avatar VARCHAR(255) NULL AFTER role");
            $userData = db_select_one("SELECT * FROM users WHERE id = ?", [$userId]);
        }
    } catch (Throwable $e) {
        // UPDATE bên dưới sẽ trả lỗi rõ nếu schema không cho phép thay đổi.
    }

    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Không nhận được ảnh tải lên.');
    }

    $file = $_FILES['avatar'];
    if ((int)$file['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('Ảnh phải nhỏ hơn hoặc bằng 5 MB.');
    }

    $imageInfo = @getimagesize($file['tmp_name']);
    if (!$imageInfo) {
        throw new RuntimeException('Tệp tải lên không phải ảnh hợp lệ.');
    }

    [$imgW, $imgH] = $imageInfo;
    if ($imgW < 300 || $imgH < 300) {
        throw new RuntimeException('Ảnh quá nhỏ. Vui lòng chọn ảnh có chiều rộng và chiều cao tối thiểu 300 x 300 px.');
    }

    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    $mime = $imageInfo['mime'] ?? '';
    if (!isset($allowedMimes[$mime])) {
        throw new RuntimeException('Chỉ chấp nhận ảnh JPG, PNG hoặc WEBP.');
    }

    $avatarUploadDir = __DIR__ . '/../assets/uploads/avatars';
    if (!is_dir($avatarUploadDir) && !@mkdir($avatarUploadDir, 0755, true) && !is_dir($avatarUploadDir)) {
        throw new RuntimeException('Không thể tạo thư mục lưu ảnh đại diện.');
    }

    $ext = $allowedMimes[$mime];
    $filename = 'avatar_' . $userId . '_' . bin2hex(random_bytes(10)) . '.' . $ext;
    $target = $avatarUploadDir . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        throw new RuntimeException('Không thể lưu ảnh lên máy chủ.');
    }

    $avatarPath = 'assets/uploads/avatars/' . $filename;
    $oldAvatar = trim((string)($userData['avatar'] ?? ''));

    db_execute('UPDATE users SET avatar = ? WHERE id = ?', [$avatarPath, $userId]);

    // Đồng bộ avatar vào session để hiện ngay ở phần bình luận sau khi upload.
    if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
        $_SESSION['user']['avatar'] = $avatarPath;
    }

    // Xóa ảnh cũ do ứng dụng quản lý.
    if ($oldAvatar !== '' && str_starts_with($oldAvatar, 'assets/uploads/avatars/')) {
        $oldFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . $oldAvatar;
        if (is_file($oldFile)) {
            @unlink($oldFile);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Cập nhật ảnh đại diện thành công!',
        'avatar_url' => base_url($avatarPath) . '?v=' . time(),
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    if (http_response_code() < 400) {
        http_response_code(422);
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
exit;
