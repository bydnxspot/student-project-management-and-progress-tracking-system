<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('student');
$stu = student_rec(); $proj = student_project((int)$stu['id']);
$pageTitle = 'Supervisor Feedback'; include __DIR__ . '/../includes/header.php';
if (!$proj): ?><div class="card p-4 text-muted">No project has been assigned to you yet.</div><?php include __DIR__ . '/../includes/footer.php'; exit; endif;
$st = db()->prepare('SELECT f.*, u.full_name, m.title mtitle, m.milestone_no, s.title stitle FROM supervisor_feedback f
  JOIN project_milestones m ON m.id=f.milestone_id JOIN supervisors sp ON sp.id=f.supervisor_id JOIN users u ON u.id=sp.user_id
  LEFT JOIN submissions s ON s.id=f.submission_id WHERE m.project_id=? ORDER BY f.created_at DESC, f.id DESC');
$st->execute([$proj['id']]); $rows = $st->fetchAll();
$rev = array_filter($rows, fn($r) => in_array($r['decision'], ['Revision Required', 'Rejected'], true));
?>
<?php if ($rev): ?><div class="alert alert-danger">You have feedback requiring corrections. Review the comments below and resubmit from the <a href="<?= e(url('student/upload.php')) ?>">upload page</a>.</div><?php endif; ?>
<div class="card"><div class="card-header">Feedback &amp; corrections</div><ul class="list-group list-group-flush">
<?php foreach ($rows as $r): ?><li class="list-group-item">
 <div class="d-flex justify-content-between flex-wrap"><strong><?= (int)$r['milestone_no'] ?>. <?= e($r['mtitle']) ?><?= $r['stitle'] ? ' — ' . e($r['stitle']) : '' ?></strong><?= badge($r['decision']) ?></div>
 <p class="mb-1 mt-1"><?= nl2br(e($r['comment'])) ?></p><small class="text-muted"><?= e($r['full_name']) ?> &middot; <?= e(date('d M Y H:i', strtotime($r['created_at']))) ?></small></li>
<?php endforeach; if (!$rows): ?><li class="list-group-item text-muted">No feedback has been given yet.</li><?php endif; ?></ul></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
