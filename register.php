<?php
require_once __DIR__ . '/includes/auth.php';
if (is_logged_in()) redirect(home_for($_SESSION['role']));
$errors = []; $v = ['full_name'=>'','email'=>'','matric_no'=>'','department'=>'','level'=>'400','phone'=>''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($v as $k => $_) $v[$k] = trim($_POST[$k] ?? '');
    $v['email'] = strtolower($v['email']);
    $pw = $_POST['password'] ?? ''; $pw2 = $_POST['password2'] ?? '';
    if (strlen($v['full_name']) < 3) $errors[] = 'Enter your full name.';
    if (!filter_var($v['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if (!preg_match('/^[A-Za-z0-9\/\-]{4,30}$/', $v['matric_no'])) $errors[] = 'Matric number must be 4-30 letters, numbers, / or -.';
    if ($v['department'] === '') $errors[] = 'Department is required.';
    if (!in_array($v['level'], ['100','200','300','400','500'], true)) $errors[] = 'Select a valid level.';
    if (strlen($pw) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($pw !== $pw2) $errors[] = 'Passwords do not match.';
    if (!$errors) {
        $c = db()->prepare('SELECT COUNT(*) FROM users WHERE email=?'); $c->execute([$v['email']]);
        if ($c->fetchColumn()) $errors[] = 'That email is already registered.';
        $c = db()->prepare('SELECT COUNT(*) FROM students WHERE matric_no=?'); $c->execute([$v['matric_no']]);
        if ($c->fetchColumn()) $errors[] = 'That matric number is already registered.';
    }
    if (!$errors) {
        $pdo = db(); $pdo->beginTransaction();
        $pdo->prepare('INSERT INTO users (full_name,email,password_hash,role,status) VALUES (?,?,?,?,?)')->execute([$v['full_name'],$v['email'],password_hash($pw, PASSWORD_DEFAULT),'student','active']);
        $newId = (int)$pdo->lastInsertId();
        $pdo->prepare('INSERT INTO students (user_id,matric_no,department,level,phone) VALUES (?,?,?,?,?)')->execute([$newId,$v['matric_no'],$v['department'],$v['level'],$v['phone']]);
        $pdo->commit();
        $_SESSION['user_id'] = $newId; log_activity('Registration', 'New student registered: ' . $v['matric_no']); $_SESSION['user_id'] = null;
        foreach ($pdo->query("SELECT id FROM users WHERE role='admin'")->fetchAll() as $a) notify((int)$a['id'], 'New student registered: ' . $v['full_name'] . ' (' . $v['matric_no'] . '). Assign a supervisor and project.', 'admin/project_add.php');
        redirect('login.php?msg=registered');
    }
}
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Student Registration | SPMPTS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="<?= e(url('assets/css/style.css')) ?>" rel="stylesheet"></head>
<body><div class="auth-wrap"><div class="auth-card" style="max-width:560px">
<div class="text-center text-white mb-3"><h1 class="h4 mb-1">Student Registration</h1><small>An administrator will assign your supervisor and project</small></div>
<div class="card p-4">
<?php foreach ($errors as $er): ?><div class="alert alert-danger py-2"><?= e($er) ?></div><?php endforeach; ?>
<form method="post"><?= csrf_field() ?>
<div class="row g-3">
 <div class="col-12"><label class="form-label">Full name</label><input name="full_name" class="form-control" value="<?= e($v['full_name']) ?>" required></div>
 <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= e($v['email']) ?>" required></div>
 <div class="col-md-6"><label class="form-label">Matric number</label><input name="matric_no" class="form-control" value="<?= e($v['matric_no']) ?>" required></div>
 <div class="col-md-6"><label class="form-label">Department</label><input name="department" class="form-control" value="<?= e($v['department']) ?>" required></div>
 <div class="col-md-3"><label class="form-label">Level</label><select name="level" class="form-select"><?php foreach (['100','200','300','400','500'] as $l): ?><option <?= $v['level']===$l?'selected':'' ?>><?= $l ?></option><?php endforeach; ?></select></div>
 <div class="col-md-3"><label class="form-label">Phone</label><input name="phone" class="form-control" value="<?= e($v['phone']) ?>"></div>
 <div class="col-md-6"><label class="form-label">Password</label><input type="password" name="password" class="form-control" minlength="8" required></div>
 <div class="col-md-6"><label class="form-label">Confirm password</label><input type="password" name="password2" class="form-control" minlength="8" required></div>
</div>
<button class="btn btn-primary w-100 mt-3">Create account</button>
</form>
<div class="text-center mt-3 small">Already registered? <a href="<?= e(url('login.php')) ?>">Log in</a></div>
</div></div></div></body></html>
