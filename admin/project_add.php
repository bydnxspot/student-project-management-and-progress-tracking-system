<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
$students = db()->query('SELECT s.id, s.matric_no, u.full_name FROM students s JOIN users u ON u.id=s.user_id WHERE u.status="active" AND NOT EXISTS (SELECT 1 FROM projects p WHERE p.student_id=s.id) ORDER BY u.full_name')->fetchAll();
$sups = db()->query('SELECT sp.id, u.full_name FROM supervisors sp JOIN users u ON u.id=sp.user_id WHERE u.status="active" ORDER BY u.full_name')->fetchAll();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $sid = (int)($_POST['student_id'] ?? 0); $supId = (int)($_POST['supervisor_id'] ?? 0); $title = trim($_POST['title'] ?? ''); $desc = trim($_POST['description'] ?? '');
    if (!in_array($sid, array_map(fn($s) => (int)$s['id'], $students), true)) $errors[] = 'Select a student who does not yet have a project.';
    if (!in_array($supId, array_map(fn($s) => (int)$s['id'], $sups), true)) $errors[] = 'Select a supervisor.';
    if (strlen($title) < 5 || strlen($title) > 255) $errors[] = 'Enter a project title (5-255 characters).';
    if (!$errors) {
        $pdo = db(); $pdo->beginTransaction();
        $pdo->prepare('INSERT INTO projects (student_id,supervisor_id,title,description,status,progress) VALUES (?,?,?,?,?,0)')->execute([$sid, $supId, $title, $desc, 'Not Started']);
        $pid = (int)$pdo->lastInsertId();
        create_milestones($pid);
        $pdo->prepare('UPDATE students SET supervisor_id=? WHERE id=?')->execute([$supId, $sid]);
        $pdo->commit();
        $stu = db()->prepare('SELECT user_id FROM students WHERE id=?'); $stu->execute([$sid]); $su = db()->prepare('SELECT user_id FROM supervisors WHERE id=?'); $su->execute([$supId]);
        notify((int)$stu->fetchColumn(), 'Your project "' . $title . '" has been created and a supervisor assigned.', 'student/project.php');
        notify((int)$su->fetchColumn(), 'A new student project was assigned to you: ' . $title, 'supervisor/students.php');
        log_activity('Project created', $title); flash('Project created with seven milestones.'); redirect('admin/projects.php');
    }
}
$pageTitle = 'Create Project'; include __DIR__ . '/../includes/header.php';
foreach ($errors as $er): ?><div class="alert alert-danger py-2"><?= e($er) ?></div><?php endforeach; ?>
<div class="card" style="max-width:760px"><div class="card-header">New project record</div><div class="card-body">
<?php if (!$students): ?><p class="text-muted mb-0">All active students already have a project. <a href="<?= e(url('admin/user_add.php')) ?>">Add a student</a> first.</p><?php elseif (!$sups): ?><p class="text-muted mb-0">No active supervisors exist. <a href="<?= e(url('admin/user_add.php')) ?>">Add a supervisor</a> first.</p><?php else: ?>
<form method="post"><?= csrf_field() ?><div class="row g-3">
 <div class="col-md-6"><label class="form-label">Student</label><select name="student_id" class="form-select" required><option value="">— Select —</option><?php foreach ($students as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['full_name']) ?> (<?= e($s['matric_no']) ?>)</option><?php endforeach; ?></select></div>
 <div class="col-md-6"><label class="form-label">Supervisor</label><select name="supervisor_id" class="form-select" required><option value="">— Select —</option><?php foreach ($sups as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['full_name']) ?></option><?php endforeach; ?></select></div>
 <div class="col-12"><label class="form-label">Project title</label><input name="title" class="form-control" maxlength="255" required value="<?= e($_POST['title'] ?? '') ?>"></div>
 <div class="col-12"><label class="form-label">Description</label><textarea name="description" rows="4" class="form-control"><?= e($_POST['description'] ?? '') ?></textarea></div>
</div><p class="small text-muted mt-3 mb-2">The seven standard milestones are generated automatically using the session start date in Settings.</p>
<button class="btn btn-primary">Create project</button> <a class="btn btn-link" href="<?= e(url('admin/projects.php')) ?>">Cancel</a></form><?php endif; ?>
</div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
