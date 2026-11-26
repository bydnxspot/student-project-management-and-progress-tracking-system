<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('student');
$stu = student_rec(); $errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (isset($_POST['save_profile'])) {
        $name = trim($_POST['full_name'] ?? ''); $phone = trim($_POST['phone'] ?? ''); $dept = trim($_POST['department'] ?? '');
        if (strlen($name) < 3) $errors[] = 'Enter your full name.';
        if ($dept === '') $errors[] = 'Department is required.';
        if ($phone !== '' && !preg_match('/^[0-9+\- ]{6,20}$/', $phone)) $errors[] = 'Enter a valid phone number.';
        if (!$errors) {
            db()->prepare('UPDATE users SET full_name=? WHERE id=?')->execute([$name, uid()]);
            db()->prepare('UPDATE students SET phone=?, department=? WHERE user_id=?')->execute([$phone, $dept, uid()]);
            $_SESSION['name'] = $name; log_activity('Profile update', 'Student updated profile');
            flash('Profile updated.'); redirect('student/profile.php');
        }
    } elseif (isset($_POST['change_password'])) {
        $cur = $_POST['current'] ?? ''; $new = $_POST['new'] ?? ''; $new2 = $_POST['new2'] ?? '';
        $h = db()->prepare('SELECT password_hash FROM users WHERE id=?'); $h->execute([uid()]);
        if (!password_verify($cur, (string)$h->fetchColumn())) $errors[] = 'Current password is incorrect.';
        if (strlen($new) < 8) $errors[] = 'New password must be at least 8 characters.';
        if ($new !== $new2) $errors[] = 'New passwords do not match.';
        if (!$errors) {
            db()->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([password_hash($new, PASSWORD_DEFAULT), uid()]);
            log_activity('Password change', 'Student changed password'); flash('Password changed.'); redirect('student/profile.php');
        }
    }
}
$pageTitle = 'My Profile'; include __DIR__ . '/../includes/header.php';
foreach ($errors as $er): ?><div class="alert alert-danger py-2"><?= e($er) ?></div><?php endforeach; ?>
<div class="row g-3">
<div class="col-lg-7"><div class="card"><div class="card-header">Profile</div><div class="card-body"><form method="post"><?= csrf_field() ?>
 <div class="mb-3"><label class="form-label">Full name</label><input name="full_name" class="form-control" value="<?= e($stu['full_name']) ?>" required></div>
 <div class="row"><div class="col-md-6 mb-3"><label class="form-label">Email (login)</label><input class="form-control" value="<?= e($stu['email']) ?>" disabled></div>
 <div class="col-md-6 mb-3"><label class="form-label">Matric number</label><input class="form-control" value="<?= e($stu['matric_no']) ?>" disabled></div></div>
 <div class="row"><div class="col-md-6 mb-3"><label class="form-label">Department</label><input name="department" class="form-control" value="<?= e($stu['department']) ?>" required></div>
 <div class="col-md-6 mb-3"><label class="form-label">Phone</label><input name="phone" class="form-control" value="<?= e($stu['phone']) ?>"></div></div>
 <button name="save_profile" value="1" class="btn btn-primary">Save changes</button></form></div></div></div>
<div class="col-lg-5"><div class="card"><div class="card-header">Change password</div><div class="card-body"><form method="post"><?= csrf_field() ?>
 <div class="mb-3"><label class="form-label">Current password</label><input type="password" name="current" class="form-control" required></div>
 <div class="mb-3"><label class="form-label">New password</label><input type="password" name="new" class="form-control" minlength="8" required></div>
 <div class="mb-3"><label class="form-label">Confirm new password</label><input type="password" name="new2" class="form-control" minlength="8" required></div>
 <button name="change_password" value="1" class="btn btn-primary">Update password</button></form></div></div></div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
