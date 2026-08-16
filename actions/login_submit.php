<?php
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();
    $user = attempt_login($_POST['username'], $_POST['password']);
    if ($user) {
        login_user($user);
        header('Location: ../index.php');
    } else {
        flash('Sai thông tin đăng nhập!', 'error');
        header('Location: ../index.php?page=login');
    }
}
?> 
 
