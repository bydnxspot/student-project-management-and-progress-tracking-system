<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('supervisor');
$sup = supervisor_rec();
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$q = db()->prepare('SELECT s.*, m.title mtitle, m.milestone_no, m.completion mcomp, m.status mstatus, p.title ptitle, p.id pid, st.matric_no, st.user_id stu_user, u.full_name
  FROM submissions s JOIN projects p ON p.id=s.project_id JOIN project_milestones m ON m.id=s.milestone_id JOIN students st ON st.id=s.student_id JOIN users u ON u.id=st.user_id
  WHERE s.id=? AND p.supervisor_id=?');
$q->execute([$id, $sup['id']]); $sub = $q->fetch();
if (!$sub) { http_response_code(404); exit('Submission not found or not assigned to you. <a href="' . e(url('supervisor/submissions.php')) . '">Back</a>'); }
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $decision = $_POST['decision'] ?? ''; $comment = trim($_POST['comment'] ?? '');
    if (!in_array($decision, ['Approved', 'Rejected', 'Revision Required', 'Under Review'], true)) $errors[] = 'Choose a decision.';
    if ($decision !== 'Under Review' && strlen($comment) < 5) $errors[] = 'Please enter feedback (at least a few words) for the student.';
    if (!$errors) {
        $pdo = db(); $pdo->beginTransaction();
        $pdo->prepare('UPDATE submissions SET status=?, reviewed_at=NOW() WHERE id=?')->execute([$decision, $id]);
        if ($decision === 'Approved') $pdo->prepare("UPDATE project_milestones SET status='Approved', completion=100 WHERE id=?")->execute([$sub['milestone_id']]);
        elseif ($decision === 'Revision Required') $pdo->prepare("UPDATE project_milestones SET status='Revision Required', completion=LEAST(completion,60) WHERE id=?")->execute([$sub['milestone_id']]);
        elseif ($decision === 'Rejected') $pdo->prepare("UPDATE project_milestones SET status='Revision Required', completion=LEAST(completion,40) WHERE id=?")->execute([$sub['milestone_id']]);
        else $pdo->prepare("UPDATE project_milestones SET status='Under Review' WHERE id=?")->execute([$sub['milestone_id']]);
        if ($comment !== '' || $decision !== 'Under Review')
            $pdo->prepare('INSERT INTO supervisor_feedback (submission_id,milestone_id,supervisor_id,comment,decision) VALUES (?,?,?,?,?)')->execute([$id, $sub['milestone_id'], $sup['id'], $comment, $decision]);
        $pdo->commit();
        recalc_progress((int)$sub['pid']);
        log_activity('Review', $decision . ': "' . $sub['title'] . '" by ' . $sub['full_name']);
        notify((int)$sub['stu_user'], 'Your submission "' . $sub['title'] . '" is now: ' . $decision . '.', 'student/feedback.php');
        flash('Review saved: ' . $decision . '.');
        redirect('supervisor/review.php?id=' . $id);
    }
}
$fb = db()->prepare('SELECT f.*, u.full_name FROM supervisor_feedback f JOIN supervisors s ON s.id=f.supervisor_id JOIN users u ON u.id=s.user_id WHERE f.submission_id=? ORDER BY f.created_at DESC, f.id DESC'); $fb->execute([$id]); $fb = $fb->fetchAll();
$pageTitle = 'Review Submission'; include __DIR__ . '/../includes/header.php';
foreach ($errors as $er): ?><div class="alert alert-danger py-2"><?= e($er) ?></div><?php endforeach; ?>
<div class="row g-3">
<div class="col-lg-6"><div class="card"><div class="card-header">Submission details</div><div class="card-body">
 <h5><?= e($sub['title']) ?></h5>
 <dl class="row mb-0">
  <dt class="col-sm-4">Student</dt><dd class="col-sm-8"><?= e($sub['full_name']) ?> (<?= e($sub['matric_no']) ?>)</dd>
  <dt class="col-sm-4">Project</dt><dd class="col-sm-8"><?= e($sub['ptitle']) ?></dd>
  <dt class="col-sm-4">Milestone</dt><dd class="col-sm-8"><?= (int)$sub['milestone_no'] ?>. <?= e($sub['mtitle']) ?></dd>
  <dt class="col-sm-4">Submitted</dt><dd class="col-sm-8"><?= e(date('d M Y H:i', strtotime($sub['submitted_at']))) ?></dd>
  <dt class="col-sm-4">Status</dt><dd class="col-sm-8"><?= badge($sub['status']) ?></dd>
  <dt class="col-sm-4">Notes</dt><dd class="col-sm-8"><?= $sub['notes'] ? nl2br(e($sub['notes'])) : '—' ?></dd>
  <dt class="col-sm-4">Document</dt><dd class="col-sm-8"><?php if ($sub['file_path']): ?><a href="<?= e(url('download.php?id=' . (int)$sub['id'])) ?>"><?= e($sub['file_name']) ?></a><?php else: ?>—<?php endif; ?></dd>
 </dl></div></div></div>
<div class="col-lg-6"><div class="card"><div class="card-header">Your decision &amp; feedback</div><div class="card-body">
 <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
  <div class="mb-3"><label class="form-label">Decision</label><select name="decision" class="form-select" required>
   <option value="">— Select —</option><option>Under Review</option><option>Approved</option><option>Revision Required</option><option>Rejected</option></select></div>
  <div class="mb-3"><label class="form-label">Feedback / corrections</label><textarea name="comment" rows="5" class="form-control"></textarea></div>
  <button class="btn btn-primary">Save review</button> <a href="<?= e(url('supervisor/submissions.php')) ?>" class="btn btn-link">Back to list</a></form></div></div></div>
</div>
<div class="card mt-3"><div class="card-header">Feedback history for this submission</div><ul class="list-group list-group-flush">
<?php foreach ($fb as $f): ?><li class="list-group-item"><div class="d-flex justify-content-between"><small class="text-muted"><?= e($f['full_name']) ?> &middot; <?= e(date('d M Y H:i', strtotime($f['created_at']))) ?></small><?= badge($f['decision']) ?></div><?= nl2br(e($f['comment'])) ?></li><?php endforeach; if (!$fb): ?><li class="list-group-item text-muted">No feedback recorded yet.</li><?php endif; ?></ul></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
