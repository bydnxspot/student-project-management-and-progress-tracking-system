<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
$sups = db()->query('SELECT sp.id, u.full_name FROM supervisors sp JOIN users u ON u.id=sp.user_id WHERE u.status="active" ORDER BY u.full_name')->fetchAll();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $pid = (int)($_POST['project_id'] ?? 0);
    $p = db()->prepare('SELECT p.*, s.user_id stu_user, s.id sid FROM projects p JOIN students s ON s.id=p.student_id WHERE p.id=?'); $p->execute([$pid]); $p = $p->fetch();
    if (!$p) { flash('Project not found.', 'danger'); redirect('admin/projects.php'); }
    if (isset($_POST['save'])) {
        $title = trim($_POST['title'] ?? ''); $supId = (int)($_POST['supervisor_id'] ?? 0);
        if (strlen($title) < 5) { flash('Project title must be at least 5 characters.', 'danger'); redirect('admin/projects.php'); }
        if ($supId && !in_array($supId, array_map(fn($s) => (int)$s['id'], $sups), true)) { flash('Invalid supervisor.', 'danger'); redirect('admin/projects.php'); }
        db()->prepare('UPDATE projects SET title=?, supervisor_id=? WHERE id=?')->execute([$title, $supId ?: null, $pid]);
        db()->prepare('UPDATE students SET supervisor_id=? WHERE id=?')->execute([$supId ?: null, $p['sid']]);
        if ($supId && (int)$p['supervisor_id'] !== $supId) {
            $su = db()->prepare('SELECT user_id FROM supervisors WHERE id=?'); $su->execute([$supId]);
            notify((int)$su->fetchColumn(), 'A project has been assigned to you: ' . $title, 'supervisor/students.php');
            notify((int)$p['stu_user'], 'Your supervisor assignment was updated.', 'student/project.php');
        }
        log_activity('Project updated', $title); flash('Project updated.');
    } elseif (isset($_POST['delete'])) {
        db()->prepare('DELETE FROM projects WHERE id=?')->execute([$pid]);
        log_activity('Project deleted', $p['title']); flash('Project deleted.');
    }
    redirect('admin/projects.php');
}
$rows = db()->query('SELECT p.*, u.full_name, s.matric_no FROM projects p JOIN students s ON s.id=p.student_id JOIN users u ON u.id=s.user_id ORDER BY p.created_at DESC, p.id DESC')->fetchAll();
$pageTitle = 'Manage Projects'; include __DIR__ . '/../includes/header.php';
?>
<div class="card"><div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">All projects
 <div class="d-flex gap-2"><input type="search" class="form-control form-control-sm" placeholder="Search..." data-table-search="#t">
 <select class="form-select form-select-sm" data-table-filter="#t" data-col="4"><option value="">All status</option><option>Not Started</option><option>In Progress</option><option>Completed</option></select>
 <a class="btn btn-sm btn-primary text-nowrap" href="<?= e(url('admin/project_add.php')) ?>">+ Create project</a></div></div>
<div class="table-responsive"><table class="table mb-0 align-middle" id="t"><thead><tr><th>Student</th><th style="min-width:260px">Title</th><th style="min-width:190px">Supervisor</th><th style="width:130px">Progress</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): $f = 'f' . (int)$r['id']; ?><tr>
<td><form id="<?= $f ?>" method="post"><?= csrf_field() ?><input type="hidden" name="project_id" value="<?= (int)$r['id'] ?>"></form><?= e($r['full_name']) ?><br><small class="text-muted"><?= e($r['matric_no']) ?></small></td>
<td><input form="<?= $f ?>" name="title" class="form-control form-control-sm" value="<?= e($r['title']) ?>"></td>
<td><select form="<?= $f ?>" name="supervisor_id" class="form-select form-select-sm"><option value="0">— Unassigned —</option><?php foreach ($sups as $s): ?><option value="<?= (int)$s['id'] ?>" <?= (int)$r['supervisor_id'] === (int)$s['id'] ? 'selected' : '' ?>><?= e($s['full_name']) ?></option><?php endforeach; ?></select></td>
<td><?= progress_bar((int)$r['progress']) ?></td><td><?= badge($r['status']) ?></td>
<td class="text-nowrap"><button form="<?= $f ?>" name="save" value="1" class="btn btn-sm btn-primary">Save</button>
<button form="<?= $f ?>" name="delete" value="1" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this project and all its milestones, submissions and feedback?')">Delete</button></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="6" class="text-muted">No projects yet.</td></tr><?php endif; ?></tbody></table></div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
