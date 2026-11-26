<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('supervisor');
$sup = supervisor_rec(); $errors = [];
$opts = db()->prepare('SELECT m.id, m.milestone_no, m.title, u.full_name, p.id pid, s.user_id stu_user FROM project_milestones m JOIN projects p ON p.id=m.project_id JOIN students s ON s.id=p.student_id JOIN users u ON u.id=s.user_id WHERE p.supervisor_id=? ORDER BY u.full_name, m.milestone_no');
$opts->execute([$sup['id']]); $opts = $opts->fetchAll();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $mid = (int)($_POST['milestone_id'] ?? 0); $comment = trim($_POST['comment'] ?? ''); $target = null;
    foreach ($opts as $o) if ((int)$o['id'] === $mid) $target = $o;
    if (!$target) $errors[] = 'Select a valid student milestone.';
    if (strlen($comment) < 5) $errors[] = 'Enter your feedback.';
    if (!$errors) {
        db()->prepare("INSERT INTO supervisor_feedback (submission_id,milestone_id,supervisor_id,comment,decision) VALUES (NULL,?,?,?, 'Comment')")->execute([$mid, $sup['id'], $comment]);
        notify((int)$target['stu_user'], 'New supervisor comment on "' . $target['title'] . '".', 'student/feedback.php');
        log_activity('Feedback', 'General feedback to ' . $target['full_name'] . ' on milestone ' . $target['milestone_no']);
        flash('Feedback sent to the student.'); redirect('supervisor/feedback.php');
    }
}
$h = db()->prepare('SELECT f.*, m.title mtitle, m.milestone_no, u.full_name FROM supervisor_feedback f JOIN project_milestones m ON m.id=f.milestone_id JOIN projects p ON p.id=m.project_id JOIN students s ON s.id=p.student_id JOIN users u ON u.id=s.user_id WHERE f.supervisor_id=? ORDER BY f.created_at DESC, f.id DESC');
$h->execute([$sup['id']]); $hist = $h->fetchAll();
$pageTitle = 'Feedback'; include __DIR__ . '/../includes/header.php';
foreach ($errors as $er): ?><div class="alert alert-danger py-2"><?= e($er) ?></div><?php endforeach; ?>
<div class="card mb-3"><div class="card-header">Leave general feedback</div><div class="card-body"><form method="post"><?= csrf_field() ?>
 <div class="mb-3"><label class="form-label">Student &amp; milestone</label><select name="milestone_id" class="form-select" required><option value="">— Select —</option>
 <?php foreach ($opts as $o): ?><option value="<?= (int)$o['id'] ?>"><?= e($o['full_name']) ?> — <?= (int)$o['milestone_no'] ?>. <?= e($o['title']) ?></option><?php endforeach; ?></select></div>
 <div class="mb-3"><label class="form-label">Feedback</label><textarea name="comment" rows="3" class="form-control" required></textarea></div>
 <button class="btn btn-primary">Send feedback</button></form></div></div>
<div class="card"><div class="card-header d-flex justify-content-between align-items-center">Feedback history <input type="search" class="form-control form-control-sm w-auto" placeholder="Search..." data-table-search="#t"></div>
<div class="table-responsive"><table class="table mb-0" id="t"><thead><tr><th>Date</th><th>Student</th><th>Milestone</th><th>Decision</th><th>Comment</th></tr></thead><tbody>
<?php foreach ($hist as $r): ?><tr><td class="text-nowrap"><?= e(date('d M Y', strtotime($r['created_at']))) ?></td><td><?= e($r['full_name']) ?></td><td><?= (int)$r['milestone_no'] ?>. <?= e($r['mtitle']) ?></td><td><?= badge($r['decision']) ?></td><td><?= e($r['comment']) ?></td></tr>
<?php endforeach; if (!$hist): ?><tr><td colspan="5" class="text-muted">No feedback recorded yet.</td></tr><?php endif; ?></tbody></table></div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
