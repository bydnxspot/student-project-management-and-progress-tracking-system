<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('supervisor');
$sup = supervisor_rec();
$st = db()->prepare('SELECT s.*, m.title mtitle, m.milestone_no, st.matric_no, u.full_name FROM submissions s JOIN projects p ON p.id=s.project_id JOIN project_milestones m ON m.id=s.milestone_id JOIN students st ON st.id=s.student_id JOIN users u ON u.id=st.user_id WHERE p.supervisor_id=? ORDER BY FIELD(s.status,\'Pending\',\'Under Review\') DESC, s.submitted_at DESC');
$st->execute([$sup['id']]); $rows = $st->fetchAll();
$pageTitle = 'Student Submissions'; include __DIR__ . '/../includes/header.php';
?>
<div class="card"><div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">Submissions &amp; review history
 <div class="d-flex gap-2"><input type="search" class="form-control form-control-sm" placeholder="Search..." data-table-search="#t">
 <select class="form-select form-select-sm" data-table-filter="#t" data-col="4"><option value="">All statuses</option><option>Pending</option><option>Under Review</option><option>Approved</option><option>Rejected</option><option>Revision Required</option></select></div></div>
<div class="table-responsive"><table class="table table-hover mb-0 align-middle" id="t"><thead><tr><th>Date</th><th>Student</th><th>Milestone</th><th>Title</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><?= e(date('d M Y', strtotime($r['submitted_at']))) ?></td><td><?= e($r['full_name']) ?><br><small class="text-muted"><?= e($r['matric_no']) ?></small></td><td><?= (int)$r['milestone_no'] ?>. <?= e($r['mtitle']) ?></td><td><?= e($r['title']) ?></td><td><?= badge($r['status']) ?></td>
<td class="text-nowrap"><a class="btn btn-sm btn-primary" href="<?= e(url('supervisor/review.php?id=' . (int)$r['id'])) ?>">Review</a>
<?php if ($r['file_path']): ?> <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('download.php?id=' . (int)$r['id'])) ?>">Download</a><?php endif; ?></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="6" class="text-muted">No submissions yet.</td></tr><?php endif; ?></tbody></table></div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
