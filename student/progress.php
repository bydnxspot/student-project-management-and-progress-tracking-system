<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('student');
$stu = student_rec(); $proj = student_project((int)$stu['id']);
$pageTitle = 'Progress & History'; include __DIR__ . '/../includes/header.php';
if (!$proj): ?><div class="card p-4 text-muted">No project has been assigned to you yet.</div><?php include __DIR__ . '/../includes/footer.php'; exit; endif;
$ms = db()->prepare('SELECT * FROM project_milestones WHERE project_id=? ORDER BY milestone_no'); $ms->execute([$proj['id']]); $ms = $ms->fetchAll();
$hist = db()->prepare('SELECT s.*, m.title mtitle, m.milestone_no FROM submissions s JOIN project_milestones m ON m.id=s.milestone_id WHERE s.student_id=? ORDER BY s.submitted_at DESC'); $hist->execute([$stu['id']]); $hist = $hist->fetchAll();
?>
<div class="card mb-3 p-3"><h5 class="mb-2"><?= e($proj['title']) ?></h5><strong>Overall progress (average of all milestones)</strong><?= progress_bar((int)$proj['progress']) ?></div>
<div class="card mb-3"><div class="card-header">Milestone progress</div><div class="table-responsive"><table class="table mb-0 align-middle"><thead><tr><th>#</th><th>Milestone</th><th>Due</th><th>Status</th><th style="width:200px">Completion</th></tr></thead><tbody>
<?php foreach ($ms as $m): ?><tr><td><?= (int)$m['milestone_no'] ?></td><td><?= e($m['title']) ?></td><td><?= e(fdate($m['due_date'])) ?></td><td><?= badge($m['status']) ?></td><td><?= progress_bar((int)$m['completion']) ?></td></tr><?php endforeach; ?>
</tbody></table></div></div>
<div class="card"><div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">Submission history
 <div class="d-flex gap-2"><input type="search" class="form-control form-control-sm" placeholder="Search..." data-table-search="#h">
 <select class="form-select form-select-sm" data-table-filter="#h" data-col="3"><option value="">All statuses</option><option>Pending</option><option>Under Review</option><option>Approved</option><option>Rejected</option><option>Revision Required</option></select></div></div>
<div class="table-responsive"><table class="table mb-0" id="h"><thead><tr><th>Date</th><th>Milestone</th><th>Title</th><th>Status</th></tr></thead><tbody>
<?php foreach ($hist as $h): ?><tr><td><?= e(date('d M Y', strtotime($h['submitted_at']))) ?></td><td><?= (int)$h['milestone_no'] ?>. <?= e($h['mtitle']) ?></td><td><?= e($h['title']) ?></td><td><?= badge($h['status']) ?></td></tr><?php endforeach; if (!$hist): ?><tr><td colspan="4" class="text-muted">No submissions yet.</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
