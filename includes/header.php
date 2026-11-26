<?php
// Expects: $pageTitle (string). Auth must already be required by the calling page.
require_once __DIR__ . '/auth.php';
$role = $_SESSION['role'] ?? '';
$unread = is_logged_in() ? unread_count() : 0;
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle ?? 'Portal') ?> | SPMPTS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="<?= e(url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body>
<div class="app">
<?php include __DIR__ . '/sidebar.php'; ?>
<div class="main">
<nav class="topbar">
  <button class="btn btn-sm btn-outline-secondary d-lg-none" id="menuToggle" type="button">&#9776; Menu</button>
  <div class="topbar-title"><?= e($pageTitle ?? '') ?></div>
  <div class="ms-auto d-flex align-items-center gap-3">
    <a href="<?= e(url('notifications.php')) ?>" class="notif-link">Notifications<?php if ($unread): ?> <span class="badge rounded-pill text-bg-danger"><?= $unread ?></span><?php endif; ?></a>
    <span class="user-chip"><?= e($_SESSION['name'] ?? '') ?> <small class="text-muted">(<?= e(ucfirst($role)) ?>)</small></span>
    <a class="btn btn-sm btn-outline-primary" href="<?= e(url('logout.php')) ?>">Log out</a>
  </div>
</nav>
<main class="content">
<?php show_flash(); ?>
