<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('supervisor');
$sup = supervisor_rec(); $sid = (int)$sup['id'];
$one = function (string $sql, array $p) { $s = db()->prepare($sql); $s->execute($p); return (int)$s->fetchColumn(); };
$students = $one('SELECT COUNT(*) FROM projects WHERE supervisor_id=?', [$sid]);
$pending = $one("SELECT COUNT(*) FROM submissions s JOIN projects p ON p.id=s.project_id WHERE p.supervisor_id=? AND s.status IN ('Pending','Under Review')", [$sid]);
$avg = $one('SELECT COALESCE(ROUND(AVG(progress)),0) FROM projects WHERE supervisor_id=?', [$sid]);
$revs = $one("SELECT COUNT(*) FROM project_milestones m JOIN projects p ON p.id=m.project_id WHERE p.supervisor_id=? AND m.status='Revision Required'", [$sid]);
$pl = db()->prepare("SELECT s.*, st.matric_no, u.full_name, m.title mtitle FROM submissions s JOIN projects p ON p.id=s.project_id JOIN students st ON st.id=s.student_id JOIN users u ON u.id=st.user_id JOIN project_milestones m ON m.id=s.milestone_id WHERE p.supervisor_id=? AND s.status IN ('Pending','Under Review') ORDER BY s.submitted_at ASC LIMIT 6"); $pl->execute([$sid]); $pl = $pl->fetchAll();
$dl = db()->prepare("SELECT m.*, u.full_name FROM project_milestones m JOIN projects p ON p.id=m.project_id JOIN students st ON st.id=p.student_id JOIN users u ON u.id=st.user_id WHERE p.supervisor_id=? AND m.status<>'Approved' AND m.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) ORDER BY m.due_date LIMIT 8"); $dl->execute([$sid]); $dl = $dl->fetchAll();
$pageTitle = 'Supervisor Dashboard'; include __DIR__ . '/../includes/header.php';
?>
<div class="row g-3 mb-3">
 <div class="col-6 col-lg-3"><div class="card stat"><div class="num"><?= $students ?></div><div class="lbl">Assigned students</div></div></div>
 <div class="col-6 col-lg-3"><div class="card stat"><div class="num"><?= $pending ?></div><div class="lbl">Pending reviews</div></div></div>
 <div class="col-6 col-lg-3"><div class="card stat"><div class="num"><?= $avg ?>%</div><div class="lbl">Average progress</div></div></div>
 <div class="col-6 col-lg-3"><div class="card stat"><div class="num"><?= $revs ?></div><div class="lbl">Awaiting corrections</div></div></div>
</div>
<div class="row g-3">
 <div class="col-lg-7"><div class="card"><div class="card-header">Pending reviews</div><ul class="list-group list-group-flush">
  <?php foreach ($pl as $r): ?><li class="list-group-item d-flex justify-content-between align-items-center"><div><strong><?= e($r['title']) ?></strong><br><small class="text-muted"><?= e($r['full_name']) ?> (<?= e($r['matric_no']) ?>) &middot; <?= e($r['mtitle']) ?> &middot; <?= e(date('d M', strtotime($r['submitted_at']))) ?></small></div><a class="btn btn-sm btn-primary" href="<?= e(url('supervisor/review.php?id=' . (int)$r['id'])) ?>">Review</a></li><?php endforeach; if (!$pl): ?><li class="list-group-item text-muted">No submissions waiting for review.</li><?php endif; ?></ul></div></div>
 <div class="col-lg-5"><div class="card"><div class="card-header">Deadlines in the next 30 days</div><ul class="list-group list-group-flush">
  <?php foreach ($dl as $d): ?><li class="list-group-item"><strong><?= e($d['full_name']) ?></strong><br><small><?= e($d['title']) ?> &middot; due <?= e(fdate($d['due_date'])) ?> &middot; <?= e($d['status']) ?></small></li><?php endforeach; if (!$dl): ?><li class="list-group-item text-muted">No approaching deadlines.</li><?php endif; ?></ul></div></div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
