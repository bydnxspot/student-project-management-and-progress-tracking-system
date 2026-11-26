<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['user_id'] ?? 0);
    if ($id === uid()) { flash('You cannot change your own account status here.', 'warning'); redirect('admin/users.php'); }
    $u = db()->prepare('SELECT * FROM users WHERE id=?'); $u->execute([$id]); $u = $u->fetch();
    if ($u && isset($_POST['toggle'])) {
        $new = $u['status'] === 'active' ? 'inactive' : 'active';
        db()->prepare('UPDATE users SET status=? WHERE id=?')->execute([$new, $id]);
        log_activity('Account ' . ($new === 'active' ? 'activated' : 'deactivated'), $u['full_name'] . ' (' . $u['email'] . ')');
        flash($u['full_name'] . ' is now ' . $new . '.');
    }
    redirect('admin/users.php');
}
$rows = db()->query("SELECT u.*, s.matric_no, sp.staff_no FROM users u LEFT JOIN students s ON s.user_id=u.id LEFT JOIN supervisors sp ON sp.user_id=u.id ORDER BY FIELD(u.role,'admin','supervisor','student'), u.full_name")->fetchAll();
$pageTitle = 'Manage Users'; include __DIR__ . '/../includes/header.php';
?>
<div class="card"><div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">All users
 <div class="d-flex gap-2"><input type="search" class="form-control form-control-sm" placeholder="Search name / email / ID..." data-table-search="#t">
 <select class="form-select form-select-sm" data-table-filter="#t" data-col="2"><option value="">All roles</option><option>admin</option><option>supervisor</option><option>student</option></select>
 <select class="form-select form-select-sm" data-table-filter="#t" data-col="4"><option value="">All status</option><option>active</option><option>inactive</option></select>
 <a class="btn btn-sm btn-primary text-nowrap" href="<?= e(url('admin/user_add.php')) ?>">+ Add user</a></div></div>
<div class="table-responsive"><table class="table table-hover mb-0 align-middle" id="t"><thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Matric / Staff No</th><th>Status</th><th>Last login</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><?= e($r['full_name']) ?></td><td><?= e($r['email']) ?></td><td><?= e($r['role']) ?></td><td><?= e($r['matric_no'] ?? $r['staff_no'] ?? '—') ?></td><td><?= badge($r['status']) ?></td><td><?= $r['last_login'] ? e(date('d M Y H:i', strtotime($r['last_login']))) : '—' ?></td>
<td class="text-nowrap"><a class="btn btn-sm btn-outline-primary" href="<?= e(url('admin/user_edit.php?id=' . (int)$r['id'])) ?>">Edit</a>
<?php if ((int)$r['id'] !== uid()): ?><form method="post" class="d-inline" data-confirm="Change this account's status?"><?= csrf_field() ?><input type="hidden" name="user_id" value="<?= (int)$r['id'] ?>"><button name="toggle" value="1" class="btn btn-sm btn-outline-<?= $r['status'] === 'active' ? 'danger' : 'success' ?>"><?= $r['status'] === 'active' ? 'Deactivate' : 'Activate' ?></button></form><?php endif; ?></td></tr>
<?php endforeach; ?></tbody></table></div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
