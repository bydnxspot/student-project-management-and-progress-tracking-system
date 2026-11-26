<?php
require_once __DIR__ . '/includes/auth.php';
if (is_logged_in()) redirect(home_for($_SESSION['role']));
$msg = $_GET['msg'] ?? '';
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Log in | SPMPTS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="<?= e(url('assets/css/style.css')) ?>" rel="stylesheet"></head>
<body><div class="auth-wrap"><div class="auth-card">
<div class="text-center text-white mb-3"><h1 class="h4 mb-1">Student Project Management &amp; Progress Tracking System</h1><small>University Project Portal</small></div>
<div class="card p-4">
  <h2 class="h5 mb-3">Sign in</h2>
  <?php if ($msg === 'inactive'): ?><div class="alert alert-warning">Your account is inactive. Contact the administrator.</div><?php endif; ?>
  <?php if ($msg === 'loggedout'): ?><div class="alert alert-success">You have been logged out.</div><?php endif; ?>
  <?php if ($msg === 'registered'): ?><div class="alert alert-success">Registration successful. You can now log in.</div><?php endif; ?>
  <?php if ($msg === 'invalid'): ?><div class="alert alert-danger">Invalid email or password.</div><?php endif; ?>
  <form method="post" action="<?= e(url('login_process.php')) ?>">
    <?= csrf_field() ?>
    <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required autofocus></div>
    <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
    <button class="btn btn-primary w-100">Log in</button>
  </form>
  <div class="text-center mt-3 small">New student? <a href="<?= e(url('register.php')) ?>">Register here</a></div>
</div></div></div></body></html>
