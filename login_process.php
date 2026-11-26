<?php
require_once __DIR__ . '/includes/auth.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('login.php');
csrf_check();
$email = strtolower(trim($_POST['email'] ?? ''));
$pass = $_POST['password'] ?? '';
$st = db()->prepare('SELECT * FROM users WHERE email=?');
$st->execute([$email]);
$u = $st->fetch();
if (!$u || !password_verify($pass, $u['password_hash'])) {
    $_SESSION['user_id'] = null;
    redirect('login.php?msg=invalid');
}
if ($u['status'] !== 'active') redirect('login.php?msg=inactive');
session_regenerate_id(true);
$_SESSION['user_id'] = (int)$u['id'];
$_SESSION['role'] = $u['role'];
$_SESSION['name'] = $u['full_name'];
unset($_SESSION['csrf']);
db()->prepare('UPDATE users SET last_login=NOW() WHERE id=?')->execute([$u['id']]);
log_activity('Login', 'User logged in');
redirect(home_for($u['role']));
