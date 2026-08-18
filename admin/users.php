<?php
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_admin();
if (isset($_GET['toggle_ban']) && ctype_digit((string) $_GET['toggle_ban'])) {
    $userId = (int) $_GET['toggle_ban'];
    db_execute('UPDATE users SET is_banned = NOT is_banned WHERE id = ?', [$userId]);
    header('Location: ' . base_url('admin/users.php') . '?p=' . max(1, (int) ($_GET['p'] ?? 1)));
    exit;
}
$currentPage = isset($_GET['p']) && ctype_digit((string) $_GET['p'])
    ? max(1, (int) $_GET['p'])
    : 1;
$perPage = 10;

$totalUsersRow = db_select_one('SELECT COUNT(*) AS total FROM users');
$totalUsers = (int) ($totalUsersRow['total'] ?? 0);
$totalPages = max(1, (int) ceil($totalUsers / $perPage));
$currentPage = min($currentPage, $totalPages);
$offset = ($currentPage - 1) * $perPage;

$users = db_select(
    "SELECT id, username, email, role, is_banned, created_at
     FROM users
     ORDER BY id DESC
     LIMIT {$perPage} OFFSET {$offset}"
);

require_once '../includes/header.php';
?>
<div class="container admin-container" style="max-width: 1000px; margin: 30px auto; padding: 0 15px;">
    <div style="margin-bottom: 24px;">
        <h2 style="margin-bottom: 6px;">Quản lý người dùng</h2>
        <p style="margin: 0; color: #8c93a8; font-size: 14px;">Tổng cộng <?php echo $totalUsers; ?> người dùng · Trang <?php echo $currentPage; ?>/<?php echo $totalPages; ?></p>
    </div>

    <div style="overflow-x: auto; background: #12151e; border-radius: 8px; border: 1px solid #232733;">
        <table class="table-admin" style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="border-bottom: 2px solid #2a2e3d; color: #8c93a8; font-size: 14px;">
                    <th style="padding: 14px 16px;">ID</th>
                    <th style="padding: 14px 16px;">Username</th>
                    <th style="padding: 14px 16px;">Email</th>
                    <th style="padding: 14px 16px;">Vai trò</th>
                    <th style="padding: 14px 16px;">Trạng thái</th>
                    <th style="padding: 14px 16px;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 30px; color: #777;">Chưa có người dùng nào.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <tr style="border-bottom: 1px solid #1c202d;">
                            <td style="padding: 12px 16px; color: #a0a6b5;">#<?php echo (int) $user['id']; ?></td>
                            <td style="padding: 12px 16px; color: #fff; font-weight: 600;"><?php echo e($user['username']); ?></td>
                            <td style="padding: 12px 16px; color: #a0a6b5;"><?php echo e($user['email']); ?></td>
                            <td style="padding: 12px 16px; color: #a0a6b5;"><?php echo $user['role'] === 'admin' ? 'Admin' : 'User'; ?></td>
                            <td style="padding: 12px 16px; color: <?php echo $user['is_banned'] ? '#ef4444' : '#22c55e'; ?>; font-weight: 600;">
                                <?php echo $user['is_banned'] ? 'Banned' : 'Active'; ?>
                            </td>
                            <td style="padding: 12px 16px;">
                                <?php if ((int) $user['id'] !== (int) (current_user()['id'] ?? 0)): ?>
                                    <a
                                        href="users.php?toggle_ban=<?php echo (int) $user['id']; ?>&p=<?php echo $currentPage; ?>"
                                        onclick="return confirm('Bạn có chắc muốn <?php echo $user['is_banned'] ? 'mở khóa' : 'khóa'; ?> tài khoản này không?');"
                                        style="color: <?php echo $user['is_banned'] ? '#22c55e' : '#ef4444'; ?>; text-decoration: none; font-weight: 500;"
                                    >
                                        <?php echo $user['is_banned'] ? 'Mở khóa' : 'Khóa'; ?>
                                    </a>
                                <?php else: ?>
                                    <span style="color: #666;">Tài khoản hiện tại</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php render_pagination($totalUsers, $currentPage, $perPage, 'admin/users.php'); ?>
</div>
<?php require_once '../includes/footer.php'; ?>