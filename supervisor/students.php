<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('supervisor');
$sup = supervisor_rec();
$st = db()->prepare('SELECT p.*, s.matric_no, s.department, s.level, u.full_name, u.email,
  (SELECT COUNT(*) FROM submissions x WHERE x.project_id=p.id AND x.status IN (\'Pending\',\'Under Review\')) pending
  FROM projects p JOIN students s ON s.id=p.student_id JOIN users u ON u.id=s.user_id WHERE p.supervisor_id=? ORDER BY u.full_name');
$st->execute([$sup['id']]); $rows = $st->fetchAll();
$pageTitle = 'My Students'; include __DIR__ . '/../includes/header.php';
?>
<div class="card"><div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">Assigned students
 <input type="search" class="form-control form-control-sm w-auto" placeholder="Search students..." data-table-search="#t"></div>
<div class="table-responsive"><table class="table table-hover mb-0 align-middle" id="t"><thead><tr><th>Student</th><th>Matric No</th><th>Project topic</th><th style="width:160px">Progress</th><th>Status</th><th>Pending</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><strong><?= e($r['full_name']) ?></strong><br><small class="text-muted"><?= e($r['email']) ?></small></td><td><?= e($r['matric_no']) ?></td><td><?= e($r['title']) ?></td><td><?= progress_bar((int)$r['progress']) ?></td><td><?= badge($r['status']) ?></td><td><?= (int)$r['pending'] ?></td>
<td><a class="btn btn-sm btn-outline-primary" href="<?= e(url('supervisor/progress.php?project=' . (int)$r['id'])) ?>">Details</a></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="7" class="text-muted">No students have been assigned to you yet.</td></tr><?php endif; ?></tbody></table></div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
