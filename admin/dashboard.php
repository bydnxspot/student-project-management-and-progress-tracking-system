<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
$n = fn(string $sql) => (int)db()->query($sql)->fetchColumn();
$stats = [
 ['Students', $n("SELECT COUNT(*) FROM users WHERE role='student'")], ['Supervisors', $n("SELECT COUNT(*) FROM users WHERE role='supervisor'")],
 ['Projects', $n('SELECT COUNT(*) FROM projects')], ['Completed projects', $n("SELECT COUNT(*) FROM projects WHERE status='Completed'")],
 ['Average progress', $n('SELECT COALESCE(ROUND(AVG(progress)),0) FROM projects')], ['Pending reviews', $n("SELECT COUNT(*) FROM submissions WHERE status IN ('Pending','Under Review')")],
 ['Unassigned students', $n('SELECT COUNT(*) FROM students s WHERE NOT EXISTS (SELECT 1 FROM projects p WHERE p.student_id=s.id)')], ['Revision required', $n("SELECT COUNT(*) FROM project_milestones WHERE status='Revision Required'")],
];
$logs = db()->query('SELECT l.*, u.full_name FROM activity_logs l LEFT JOIN users u ON u.id=l.user_id ORDER BY l.created_at DESC, l.id DESC LIMIT 8')->fetchAll();
$byStatus = db()->query('SELECT status, COUNT(*) c FROM project_milestones GROUP BY status')->fetchAll();
$pageTitle = 'Administrator Dashboard'; include __DIR__ . '/../includes/header.php';
?>
<div class="row g-3 mb-3"><?php foreach ($stats as [$l, $v]): ?><div class="col-6 col-lg-3"><div class="card stat"><div class="num"><?= $v ?><?= $l === 'Average progress' ? '%' : '' ?></div><div class="lbl"><?= e($l) ?></div></div></div><?php endforeach; ?></div>
<div class="row g-3">
 <div class="col-lg-7"><div class="card"><div class="card-header d-flex justify-content-between">Recent activity <a href="<?= e(url('admin/logs.php')) ?>" class="small">View all</a></div><ul class="list-group list-group-flush">
 <?php foreach ($logs as $l): ?><li class="list-group-item"><strong><?= e($l['action']) ?></strong> — <?= e($l['details']) ?><br><small class="text-muted"><?= e($l['full_name'] ?? 'System') ?> &middot; <?= e(date('d M Y H:i', strtotime($l['created_at']))) ?></small></li><?php endforeach; if (!$logs): ?><li class="list-group-item text-muted">No activity yet.</li><?php endif; ?></ul></div></div>
 <div class="col-lg-5"><div class="card"><div class="card-header">Milestones by status</div><ul class="list-group list-group-flush">
 <?php foreach ($byStatus as $b): ?><li class="list-group-item d-flex justify-content-between"><?= badge($b['status']) ?><strong><?= (int)$b['c'] ?></strong></li><?php endforeach; ?></ul></div></div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
