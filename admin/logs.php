<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
$rows = db()->query('SELECT l.*, u.full_name, u.role FROM activity_logs l LEFT JOIN users u ON u.id=l.user_id ORDER BY l.created_at DESC, l.id DESC LIMIT 500')->fetchAll();
$pageTitle = 'Activity Logs'; include __DIR__ . '/../includes/header.php';
?>
<div class="card"><div class="card-header d-flex justify-content-between align-items-center">System activity (latest 500) <input type="search" class="form-control form-control-sm w-auto" placeholder="Search..." data-table-search="#t"></div>
<div class="table-responsive"><table class="table table-sm mb-0" id="t"><thead><tr><th>Time</th><th>User</th><th>Action</th><th>Details</th><th>IP</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td class="text-nowrap"><?= e(date('d M Y H:i', strtotime($r['created_at']))) ?></td><td><?= e($r['full_name'] ?? 'System') ?> <small class="text-muted"><?= e($r['role'] ?? '') ?></small></td><td><?= e($r['action']) ?></td><td><?= e($r['details']) ?></td><td><?= e($r['ip_address']) ?></td></tr><?php endforeach; ?></tbody></table></div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
