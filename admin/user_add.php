<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
$errors = []; $v = ['role'=>'student','full_name'=>'','email'=>'','id_no'=>'','department'=>'','level'=>'400','phone'=>''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($v as $k => $_) $v[$k] = trim($_POST[$k] ?? '');
    $v['email'] = strtolower($v['email']); $pw = $_POST['password'] ?? '';
    if (!in_array($v['role'], ['student','supervisor','admin'], true)) $errors[] = 'Invalid role.';
    if (strlen($v['full_name']) < 3) $errors[] = 'Enter the full name.';
    if (!filter_var($v['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email.';
    if (strlen($pw) < 8) $errors[] = 'Password must be at least 8 characters.';
    if (in_array($v['role'], ['student','supervisor'], true) && !preg_match('/^[A-Za-z0-9\/\-]{3,30}$/', $v['id_no'])) $errors[] = 'Enter a valid matric / staff number.';
    if (in_array($v['role'], ['student','supervisor'], true) && $v['department'] === '') $errors[] = 'Department is required.';
    if (!$errors) {
        $c = db()->prepare('SELECT COUNT(*) FROM users WHERE email=?'); $c->execute([$v['email']]); if ($c->fetchColumn()) $errors[] = 'Email already in use.';
        if ($v['role'] === 'student') { $c = db()->prepare('SELECT COUNT(*) FROM students WHERE matric_no=?'); $c->execute([$v['id_no']]); if ($c->fetchColumn()) $errors[] = 'Matric number already in use.'; }
        if ($v['role'] === 'supervisor') { $c = db()->prepare('SELECT COUNT(*) FROM supervisors WHERE staff_no=?'); $c->execute([$v['id_no']]); if ($c->fetchColumn()) $errors[] = 'Staff number already in use.'; }
    }
    if (!$errors) {
        $pdo = db(); $pdo->beginTransaction();
        $pdo->prepare('INSERT INTO users (full_name,email,password_hash,role,status) VALUES (?,?,?,?,?)')->execute([$v['full_name'], $v['email'], password_hash($pw, PASSWORD_DEFAULT), $v['role'], 'active']);
        $nid = (int)$pdo->lastInsertId();
        if ($v['role'] === 'student') $pdo->prepare('INSERT INTO students (user_id,matric_no,department,level,phone) VALUES (?,?,?,?,?)')->execute([$nid, $v['id_no'], $v['department'], $v['level'], $v['phone']]);
        if ($v['role'] === 'supervisor') $pdo->prepare('INSERT INTO supervisors (user_id,staff_no,department,phone) VALUES (?,?,?,?)')->execute([$nid, $v['id_no'], $v['department'], $v['phone']]);
        $pdo->commit();
        log_activity('User created', ucfirst($v['role']) . ': ' . $v['full_name'] . ' (' . $v['email'] . ')');
        flash('User account created.'); redirect('admin/users.php');
    }
}
$pageTitle = 'Add User'; include __DIR__ . '/../includes/header.php';
foreach ($errors as $er): ?><div class="alert alert-danger py-2"><?= e($er) ?></div><?php endforeach; ?>
<div class="card" style="max-width:760px"><div class="card-header">Create account</div><div class="card-body"><form method="post"><?= csrf_field() ?>
<div class="row g-3">
 <div class="col-md-4"><label class="form-label">Role</label><select name="role" class="form-select"><?php foreach (['student','supervisor','admin'] as $r): ?><option <?= $v['role']===$r?'selected':'' ?>><?= $r ?></option><?php endforeach; ?></select></div>
 <div class="col-md-8"><label class="form-label">Full name</label><input name="full_name" class="form-control" value="<?= e($v['full_name']) ?>" required></div>
 <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= e($v['email']) ?>" required></div>
 <div class="col-md-6"><label class="form-label">Temporary password</label><input type="password" name="password" class="form-control" minlength="8" required></div>
 <div class="col-md-4"><label class="form-label">Matric / Staff No <small class="text-muted">(not for admin)</small></label><input name="id_no" class="form-control" value="<?= e($v['id_no']) ?>"></div>
 <div class="col-md-4"><label class="form-label">Department</label><input name="department" class="form-control" value="<?= e($v['department']) ?>"></div>
 <div class="col-md-2"><label class="form-label">Level</label><select name="level" class="form-select"><?php foreach (['100','200','300','400','500'] as $l): ?><option <?= $v['level']===$l?'selected':'' ?>><?= $l ?></option><?php endforeach; ?></select></div>
 <div class="col-md-2"><label class="form-label">Phone</label><input name="phone" class="form-control" value="<?= e($v['phone']) ?>"></div>
</div><button class="btn btn-primary mt-3">Create user</button> <a class="btn btn-link mt-3" href="<?= e(url('admin/users.php')) ?>">Cancel</a></form></div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
