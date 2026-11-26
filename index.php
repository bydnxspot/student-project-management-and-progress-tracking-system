<?php
require_once __DIR__ . '/includes/auth.php';
if (is_logged_in()) redirect(home_for($_SESSION['role']));
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>SPMPTS | University Project Portal</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="<?= e(url('assets/css/style.css')) ?>" rel="stylesheet"></head>
<body>
<div class="hero text-center"><div class="container">
  <h1 class="h2 mb-2">Student Project Management &amp; Progress Tracking System</h1>
  <p class="lead mb-4">Track final-year project milestones, submit work, and receive supervisor feedback in one place.</p>
  <a class="btn btn-light me-2" href="<?= e(url('login.php')) ?>">Log in</a>
  <a class="btn btn-outline-light" href="<?= e(url('register.php')) ?>">Student registration</a>
</div></div>
<div class="container py-5"><div class="row g-3">
  <div class="col-md-4"><div class="card h-100 p-3"><h5>Students</h5><p class="mb-0 text-muted">View your topic and supervisor, submit documents against seven milestones and track deadlines and feedback.</p></div></div>
  <div class="col-md-4"><div class="card h-100 p-3"><h5>Supervisors</h5><p class="mb-0 text-muted">Review submissions, approve or request corrections and monitor every assigned student's progress.</p></div></div>
  <div class="col-md-4"><div class="card h-100 p-3"><h5>Administrators</h5><p class="mb-0 text-muted">Manage accounts, assign supervisors, generate reports and export CSV summaries.</p></div></div>
</div></div></body></html>
