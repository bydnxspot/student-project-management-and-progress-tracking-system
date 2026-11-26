<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('supervisor');
$sup = supervisor_rec();
$list = db()->prepare('SELECT p.id, p.title, p.progress, u.full_name, s.matric_no FROM projects p JOIN students s ON s.id=p.student_id JOIN users u ON u.id=s.user_id WHERE p.supervisor_id=? ORDER BY u.full_name');
$list->execute([$sup['id']]); $projects = $list->fetchAll();
$pid = (int)($_GET['project'] ?? $_POST['project'] ?? ($projects[0]['id'] ?? 0));
$cur = null; foreach ($projects as $p) if ((int)$p['id'] === $pid) $cur = $p;
if ($pid && !$cur) { http_response_code(403); exit('That project is not assigned to you.'); }
$errors = [];
if ($cur && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $mid = (int)($_POST['milestone_id'] ?? 0); $status = $_POST['status'] ?? ''; $comp = (int)($_POST['completion'] ?? -1);
    $chk = db()->prepare('SELECT * FROM project_milestones WHERE id=? AND project_id=?'); $chk->execute([$mid, $pid]); $m = $chk->fetch();
    if (!$m) $errors[] = 'Milestone not found.';
    if (!in_array($status, MILESTONE_STATUSES, true)) $errors[] = 'Invalid status.';
    if ($comp < 0 || $comp > 100) $errors[] = 'Completion must be between 0 and 100.';
    if (!$errors) {
        if ($status === 'Approved') $comp = 100;
        if ($status === 'Not Started') $comp = 0;
        db()->prepare('UPDATE project_milestones SET status=?, completion=? WHERE id=?')->execute([$status, $comp, $mid]);
        recalc_progress($pid);
        log_activity('Milestone update', 'Set "' . $m['title'] . '" to ' . $status . ' (' . $comp . '%) for ' . $cur['full_name']);
        $su = db()->prepare('SELECT s.user_id FROM students s JOIN projects p ON p.student_id=s.id WHERE p.id=?'); $su->execute([$pid]);
        notify((int)$su->fetchColumn(), 'Your supervisor updated milestone "' . $m['title'] . '" to ' . $status . ' (' . $comp . '%).', 'student/milestones.php');
        flash('Milestone updated and project progress recalculated.'); redirect('supervisor/progress.php?project=' . $pid);
    }
}
$ms = []; if ($cur) { $q = db()->prepare('SELECT * FROM project_milestones WHERE project_id=? ORDER BY milestone_no'); $q->execute([$pid]); $ms = $q->fetchAll();
  $cur['progress'] = (int)db()->query('SELECT progress FROM projects WHERE id=' . $pid)->fetchColumn(); }
$pageTitle = 'Student Progress'; include __DIR__ . '/../includes/header.php';
foreach ($errors as $er): ?><div class="alert alert-danger py-2"><?= e($er) ?></div><?php endforeach;
if (!$projects): ?><div class="card p-4 text-muted">No students have been assigned to you yet.</div><?php include __DIR__ . '/../includes/footer.php'; exit; endif; ?>
<form method="get" class="card p-3 mb-3"><label class="form-label">Select student</label>
 <select name="project" class="form-select" onchange="this.form.submit()"><?php foreach ($projects as $p): ?><option value="<?= (int)$p['id'] ?>" <?= (int)$p['id'] === $pid ? 'selected' : '' ?>><?= e($p['full_name']) ?> (<?= e($p['matric_no']) ?>) — <?= (int)$p['progress'] ?>%</option><?php endforeach; ?></select></form>
<?php if ($cur): ?>
<div class="card mb-3 p-3"><h5><?= e($cur['title']) ?></h5><strong>Overall progress</strong><?= progress_bar((int)$cur['progress']) ?></div>
<div class="card"><div class="card-header">Milestones</div><div class="table-responsive"><table class="table mb-0 align-middle"><thead><tr><th>#</th><th>Milestone</th><th>Due</th><th>Status</th><th>Update progress</th></tr></thead><tbody>
<?php foreach ($ms as $m): ?><tr><td><?= (int)$m['milestone_no'] ?></td><td><?= e($m['title']) ?><br><small class="text-muted"><?= (int)$m['completion'] ?>% complete</small></td><td><?= e(fdate($m['due_date'])) ?></td><td><?= badge($m['status']) ?></td>
<td><form method="post" class="d-flex gap-2 flex-wrap"><?= csrf_field() ?><input type="hidden" name="project" value="<?= $pid ?>"><input type="hidden" name="milestone_id" value="<?= (int)$m['id'] ?>">
 <select name="status" class="form-select form-select-sm w-auto"><?php foreach (MILESTONE_STATUSES as $s): ?><option <?= $s === $m['status'] ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select>
 <input type="number" name="completion" min="0" max="100" value="<?= (int)$m['completion'] ?>" class="form-control form-control-sm" style="width:80px"><button class="btn btn-sm btn-primary">Save</button></form></td></tr>
<?php endforeach; ?></tbody></table></div></div>
<?php endif; include __DIR__ . '/../includes/footer.php'; ?>
