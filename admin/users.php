<?php
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_admin();
require_once '../includes/header.php';
?>
<div class="container">
    <h2>Manage Users</h2>
    <table>
        <?php foreach (db_select("SELECT * FROM users") as $user): ?>
            <tr>
                <td><?php echo e($user['username']); ?></td>
                <td><?php echo $user['is_banned'] ? 'Banned' : 'Active'; ?></td>
                <td><a href="?toggle_ban=<?php echo $user['id']; ?>">Toggle Ban</a></td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php 
if (isset($_GET['toggle_ban'])) {
    db_execute("UPDATE users SET is_banned = NOT is_banned WHERE id = ?", [$_GET['toggle_ban']]);
    header('Location: users.php');
    exit;
}
require_once '../includes/footer.php'; 
?>