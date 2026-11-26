<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('student');
$stu = student_rec(); $proj = student_project((int)$stu['id']);
$pageTitle = 'Project Milestones'; include __DIR__ . '/../includes/header.php';
if (!$proj): ?><div class="card p-4 text-muted">No project has been assigned to you yet.</div><?php include __DIR__ . '/../includes/footer.php'; exit; endif;
$ms = db()->prepare('SELECT m.*, (SELECT COUNT(*) FROM submissions s WHERE s.milestone_id=m.id) subs,
  (SELECT f.comment FROM supervisor_feedback f WHERE f.milestone_id=m.id ORDER BY f.created_at DESC, f.id DESC LIMIT 1) last_fb
  FROM project_milestones m WHERE m.project_id=? ORDER BY m.milestone_no'); $ms->execute([$proj['id']]); $ms = $ms->fetchAll();
?>
<div class="card mb-3 p-3"><strong>Overall progress</strong><?= progress_bar((int)$proj['progress']) ?></div>
<div class="row g-3">
<?php foreach ($ms as $m): $late = $m['status'] !== 'Approved' && $m['due_date'] < date('Y-m-d'); ?>
 <div class="col-lg-6"><div class="card h-100"><div class="card-body">
  <div class="d-flex justify-content-between"><h6><?= (int)$m['milestone_no'] ?>. <?= e($m['title']) ?></h6><?= badge($m['status']) ?></div>
  <p class="text-muted small"><?= e($m['description']) ?></p>
  <div class="small mb-2">Start: <?= e(fdate($m['start_date'])) ?> &middot; Due: <strong class="<?= $late ? 'text-danger' : '' ?>"><?= e(fdate($m['due_date'])) ?></strong><?= $late ? ' (overdue)' : '' ?></div>
  <?= progress_bar((int)$m['completion']) ?>
  <?php if ($m['last_fb']): ?><div class="border-start border-3 ps-2 mt-2 small"><em>Latest feedback:</em> <?= e($m['last_fb']) ?></div><?php endif; ?>
  <?php if ($m['status'] !== 'Approved'): ?><a class="btn btn-sm btn-primary mt-3" href="<?= e(url('student/upload.php?milestone=' . (int)$m['id'])) ?>"><?= $m['status']==='Revision Required' ? 'Resubmit' : 'Submit' ?></a><?php endif; ?>
  <small class="text-muted ms-2"><?= (int)$m['subs'] ?> submission(s)</small>
 </div></div></div>
<?php endforeach; ?></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
