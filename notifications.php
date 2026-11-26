<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (isset($_POST['read_all'])) { db()->prepare('UPDATE notifications SET is_read=1 WHERE user_id=?')->execute([uid()]); flash('All notifications marked as read.'); }
    elseif (isset($_POST['read_id'])) { db()->prepare('UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?')->execute([(int)$_POST['read_id'], uid()]); }
    redirect('notifications.php');
}
$st = db()->prepare('SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC, id DESC LIMIT 100'); $st->execute([uid()]); $rows = $st->fetchAll();
$pageTitle = 'Notifications'; include __DIR__ . '/includes/header.php';
?>
<div class="card"><div class="card-header d-flex justify-content-between align-items-center">Your notifications
  <form method="post"><?= csrf_field() ?><button name="read_all" value="1" class="btn btn-sm btn-outline-primary">Mark all as read</button></form></div>
<ul class="list-group list-group-flush">
<?php foreach ($rows as $n): ?>
  <li class="list-group-item d-flex justify-content-between align-items-start <?= $n['is_read'] ? '' : 'bg-light fw-semibold' ?>">
    <div><?= e($n['message']) ?><br><small class="text-muted"><?= e(date('d M Y H:i', strtotime($n['created_at']))) ?></small>
      <?php if ($n['link']): ?> &middot; <a href="<?= e(url($n['link'])) ?>">Open</a><?php endif; ?></div>
    <?php if (!$n['is_read']): ?><form method="post"><?= csrf_field() ?><button name="read_id" value="<?= (int)$n['id'] ?>" class="btn btn-sm btn-link">Mark read</button></form><?php endif; ?>
  </li>
<?php endforeach; if (!$rows): ?><li class="list-group-item text-muted">No notifications yet.</li><?php endif; ?>
</ul></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
