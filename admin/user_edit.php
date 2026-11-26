<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$q = db()->prepare('SELECT u.*, s.id sid, s.matric_no, s.level, s.phone sphone, s.department sdept, sp.id spid, sp.staff_no, sp.phone pphone, sp.department pdept FROM users u LEFT JOIN students s ON s.user_id=u.id LEFT JOIN supervisors sp ON sp.user_id=u.id WHERE u.id=?');
$q->execute([$id]); $u = $q->fetch();
if (!$u) { http_response_code(404); exit('User not found.'); }
$sups = db()->query('SELECT sp.id, usr.full_name FROM supervisors sp JOIN users usr ON usr.id=sp.user_id WHERE usr.status="active" ORDER BY usr.full_name')->fetchAll();
$curSup = null; if ($u['sid']) { $c = db()->prepare('SELECT supervisor_id FROM projects WHERE student_id=?'); $c->execute([$u['sid']]); $curSup = $c->fetchColumn() ?: null; }
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim($_POST['full_name'] ?? ''); $email = strtolower(trim($_POST['email'] ?? '')); $status = $_POST['status'] ?? 'active';
    $dept = trim($_POST['department'] ?? ''); $phone = trim($_POST['phone'] ?? ''); $pw = $_POST['password'] ?? '';
    if (strlen($name) < 3) $errors[] = 'Enter the full name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email.';
    if (!in_array($status, ['active','inactive'], true)) $errors[] = 'Invalid status.';
    if ($id === uid() && $status !== 'active') $errors[] = 'You cannot deactivate your own account.';
    if ($pw !== '' && strlen($pw) < 8) $errors[] = 'New password must be at least 8 characters.';
    $c = db()->prepare('SELECT COUNT(*) FROM users WHERE email=? AND id<>?'); $c->execute([$email, $id]); if ($c->fetchColumn()) $errors[] = 'Email already in use by another account.';
    $supId = (int)($_POST['supervisor_id'] ?? 0);
    if ($supId && !in_array($supId, array_map(fn($s) => (int)$s['id'], $sups), true)) $errors[] = 'Invalid supervisor selected.';
    if (!$errors) {
        $pdo = db(); $pdo->beginTransaction();
        $pdo->prepare('UPDATE users SET full_name=?, email=?, status=? WHERE id=?')->execute([$name, $email, $status, $id]);
        if ($pw !== '') $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([password_hash($pw, PASSWORD_DEFAULT), $id]);
        if ($u['sid']) {
            $pdo->prepare('UPDATE students SET department=?, phone=?, level=? WHERE id=?')->execute([$dept, $phone, $_POST['level'] ?? $u['level'], $u['sid']]);
            $pdo->prepare('UPDATE students SET supervisor_id=? WHERE id=?')->execute([$supId ?: null, $u['sid']]);
            $pdo->prepare('UPDATE projects SET supervisor_id=? WHERE student_id=?')->execute([$supId ?: null, $u['sid']]);
        }
        if ($u['spid']) $pdo->prepare('UPDATE supervisors SET department=?, phone=? WHERE id=?')->execute([$dept, $phone, $u['spid']]);
        $pdo->commit();
        if ($u['sid'] && $supId && (int)$curSup !== $supId) {
            $su = db()->prepare('SELECT user_id FROM supervisors WHERE id=?'); $su->execute([$supId]);
            notify((int)$su->fetchColumn(), $name . ' has been assigned to you as a supervisee.', 'supervisor/students.php');
            notify($id, 'A supervisor has been assigned to you.', 'student/project.php');
        }
        log_activity('User updated', $name . ' (' . $email . ')'); flash('User updated.'); redirect('admin/users.php');
    }
}
$isStu = (bool)$u['sid']; $isSup = (bool)$u['spid'];
$pageTitle = 'Edit User'; include __DIR__ . '/../includes/header.php';
foreach ($errors as $er): ?><div class="alert alert-danger py-2"><?= e($er) ?></div><?php endforeach; ?>
<div class="card" style="max-width:760px"><div class="card-header">Edit <?= e($u['role']) ?> account<?= $isStu ? ' — ' . e($u['matric_no']) : ($isSup ? ' — ' . e($u['staff_no']) : '') ?></div><div class="card-body"><form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
<div class="row g-3">
 <div class="col-md-6"><label class="form-label">Full name</label><input name="full_name" class="form-control" value="<?= e($_POST['full_name'] ?? $u['full_name']) ?>" required></div>
 <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= e($_POST['email'] ?? $u['email']) ?>" required></div>
 <?php if ($isStu || $isSup): ?><div class="col-md-5"><label class="form-label">Department</label><input name="department" class="form-control" value="<?= e($u['sdept'] ?? $u['pdept']) ?>"></div>
 <div class="col-md-4"><label class="form-label">Phone</label><input name="phone" class="form-control" value="<?= e($u['sphone'] ?? $u['pphone']) ?>"></div><?php endif; ?>
 <?php if ($isStu): ?><div class="col-md-3"><label class="form-label">Level</label><select name="level" class="form-select"><?php foreach (['100','200','300','400','500'] as $l): ?><option <?= $u['level']===$l?'selected':'' ?>><?= $l ?></option><?php endforeach; ?></select></div>
 <div class="col-md-6"><label class="form-label">Assigned supervisor</label><select name="supervisor_id" class="form-select"><option value="0">— None —</option><?php foreach ($sups as $s): ?><option value="<?= (int)$s['id'] ?>" <?= (int)$curSup === (int)$s['id'] ? 'selected' : '' ?>><?= e($s['full_name']) ?></option><?php endforeach; ?></select></div><?php endif; ?>
 <div class="col-md-3"><label class="form-label">Status</label><select name="status" class="form-select"><option <?= $u['status']==='active'?'selected':'' ?>>active</option><option <?= $u['status']==='inactive'?'selected':'' ?>>inactive</option></select></div>
 <div class="col-md-6"><label class="form-label">Reset password <small class="text-muted">(leave blank to keep)</small></label><input type="password" name="password" class="form-control" minlength="8" autocomplete="new-password"></div>
</div><button class="btn btn-primary mt-3">Save changes</button> <a class="btn btn-link mt-3" href="<?= e(url('admin/users.php')) ?>">Cancel</a></form></div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
