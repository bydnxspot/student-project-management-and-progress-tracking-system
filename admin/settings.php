<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $inst = trim($_POST['institution_name'] ?? ''); $sess = trim($_POST['academic_session'] ?? ''); $start = trim($_POST['session_start_date'] ?? ''); $mb = (int)($_POST['max_upload_mb'] ?? 0);
    if ($inst === '') $errors[] = 'Institution name is required.';
    if (!preg_match('/^\d{4}\/\d{4}$/', $sess)) $errors[] = 'Academic session must look like 2026/2027.';
    $d = DateTime::createFromFormat('Y-m-d', $start); if (!$d || $d->format('Y-m-d') !== $start) $errors[] = 'Enter a valid session start date.';
    if ($mb < 1 || $mb > 50) $errors[] = 'Maximum upload size must be between 1 and 50 MB.';
    if (!$errors) {
        $up = db()->prepare('INSERT INTO academic_settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');
        foreach (['institution_name' => $inst, 'academic_session' => $sess, 'session_start_date' => $start, 'max_upload_mb' => (string)$mb] as $k => $val) $up->execute([$k, $val]);
        log_activity('Settings updated', 'Session ' . $sess . ', start ' . $start); flash('Settings saved. New projects will use the updated start date for milestone deadlines.'); redirect('admin/settings.php');
    }
}
$pageTitle = 'Academic Settings'; include __DIR__ . '/../includes/header.php';
foreach ($errors as $er): ?><div class="alert alert-danger py-2"><?= e($er) ?></div><?php endforeach; ?>
<div class="card" style="max-width:640px"><div class="card-header">Academic project settings</div><div class="card-body"><form method="post"><?= csrf_field() ?>
 <div class="mb-3"><label class="form-label">Institution name</label><input name="institution_name" class="form-control" value="<?= e($_POST['institution_name'] ?? setting('institution_name')) ?>" required></div>
 <div class="row"><div class="col-md-6 mb-3"><label class="form-label">Academic session</label><input name="academic_session" class="form-control" placeholder="2026/2027" value="<?= e($_POST['academic_session'] ?? setting('academic_session')) ?>" required></div>
 <div class="col-md-6 mb-3"><label class="form-label">Session start date</label><input type="date" name="session_start_date" class="form-control" value="<?= e($_POST['session_start_date'] ?? setting('session_start_date')) ?>" required></div></div>
 <div class="mb-3"><label class="form-label">Maximum upload size (MB)</label><input type="number" name="max_upload_mb" min="1" max="50" class="form-control" value="<?= e($_POST['max_upload_mb'] ?? setting('max_upload_mb', '10')) ?>" required><div class="form-text">Also limited by upload_max_filesize / post_max_size in php.ini.</div></div>
 <button class="btn btn-primary">Save settings</button></form></div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
