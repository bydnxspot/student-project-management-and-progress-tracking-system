<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('student');
$stu = student_rec(); $proj = student_project((int)$stu['id']);
$allowedExt = ['pdf','doc','docx','ppt','pptx','xls','xlsx','txt','zip','png','jpg','jpeg'];
$maxMb = max(1, (int)setting('max_upload_mb', '10'));
$errors = [];
if ($proj && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $mid = (int)($_POST['milestone_id'] ?? 0);
    $title = trim($_POST['title'] ?? ''); $notes = trim($_POST['notes'] ?? '');
    $m = db()->prepare('SELECT * FROM project_milestones WHERE id=? AND project_id=?'); $m->execute([$mid, $proj['id']]); $m = $m->fetch();
    if (!$m) $errors[] = 'Select a valid milestone.';
    elseif ($m['status'] === 'Approved') $errors[] = 'That milestone is already approved.';
    if (strlen($title) < 3) $errors[] = 'Enter a title for your submission.';
    if (strlen($title) > 200) $errors[] = 'Title is too long.';
    $storedName = null; $origName = null;
    $f = $_FILES['document'] ?? null;
    if ($f && $f['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($f['error'] !== UPLOAD_ERR_OK) $errors[] = 'File upload failed (error code ' . (int)$f['error'] . '). Check the maximum upload size in php.ini.';
        else {
            $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExt, true)) $errors[] = 'File type not allowed. Allowed: ' . implode(', ', $allowedExt) . '.';
            if ($f['size'] > $maxMb * 1024 * 1024) $errors[] = "File exceeds the {$maxMb} MB limit.";
            if (!$errors) {
                $storedName = bin2hex(random_bytes(16)) . '.' . $ext;
                $origName = preg_replace('/[^A-Za-z0-9._ -]/', '_', basename($f['name']));
            }
        }
    } else $errors[] = 'Please choose a document to upload.';
    if (!$errors) {
        if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
        if (!move_uploaded_file($f['tmp_name'], UPLOAD_DIR . $storedName)) $errors[] = 'Could not save the uploaded file. Check folder permissions on uploads/.';
    }
    if (!$errors) {
        $pdo = db(); $pdo->beginTransaction();
        $pdo->prepare('INSERT INTO submissions (milestone_id,project_id,student_id,title,notes,file_name,file_path,status) VALUES (?,?,?,?,?,?,?,?)')
            ->execute([$mid, $proj['id'], $stu['id'], $title, $notes, $origName, $storedName, 'Pending']);
        $pdo->prepare("UPDATE project_milestones SET status='Submitted', completion=GREATEST(completion,70) WHERE id=?")->execute([$mid]);
        $pdo->commit();
        recalc_progress((int)$proj['id']);
        log_activity('Submission', 'Submitted "' . $title . '" for milestone ' . $m['milestone_no']);
        if ($proj['supervisor_id']) { $su = db()->prepare('SELECT user_id FROM supervisors WHERE id=?'); $su->execute([$proj['supervisor_id']]); if ($uidSup = $su->fetchColumn()) notify((int)$uidSup, $stu['full_name'] . ' submitted "' . $title . '" (' . $m['title'] . ').', 'supervisor/submissions.php'); }
        flash('Submission uploaded successfully and sent to your supervisor.');
        redirect('student/upload.php');
    }
}
$pageTitle = 'Submit / Upload'; include __DIR__ . '/../includes/header.php';
if (!$proj): ?><div class="card p-4 text-muted">No project has been assigned to you yet.</div><?php include __DIR__ . '/../includes/footer.php'; exit; endif;
$ms = db()->prepare("SELECT * FROM project_milestones WHERE project_id=? AND status<>'Approved' ORDER BY milestone_no"); $ms->execute([$proj['id']]); $ms = $ms->fetchAll();
$sel = (int)($_POST['milestone_id'] ?? $_GET['milestone'] ?? 0);
$hist = db()->prepare('SELECT s.*, m.title mtitle, m.milestone_no FROM submissions s JOIN project_milestones m ON m.id=s.milestone_id WHERE s.student_id=? ORDER BY s.submitted_at DESC, s.id DESC'); $hist->execute([$stu['id']]); $hist = $hist->fetchAll();
?>
<?php foreach ($errors as $er): ?><div class="alert alert-danger py-2"><?= e($er) ?></div><?php endforeach; ?>
<div class="card mb-3"><div class="card-header">Submit project update / document</div><div class="card-body">
<?php if (!$ms): ?><p class="text-muted mb-0">All milestones are approved. Nothing left to submit.</p><?php else: ?>
<form method="post" enctype="multipart/form-data"><?= csrf_field() ?>
 <div class="row g-3">
  <div class="col-md-6"><label class="form-label">Milestone</label><select name="milestone_id" class="form-select" required>
    <option value="">— Select —</option><?php foreach ($ms as $m): ?><option value="<?= (int)$m['id'] ?>" <?= $sel === (int)$m['id'] ? 'selected' : '' ?>><?= (int)$m['milestone_no'] ?>. <?= e($m['title']) ?> (<?= e($m['status']) ?>)</option><?php endforeach; ?></select></div>
  <div class="col-md-6"><label class="form-label">Submission title</label><input name="title" class="form-control" maxlength="200" required value="<?= e($_POST['title'] ?? '') ?>"></div>
  <div class="col-12"><label class="form-label">Update notes</label><textarea name="notes" rows="3" class="form-control"><?= e($_POST['notes'] ?? '') ?></textarea></div>
  <div class="col-12"><label class="form-label">Document (<?= e(implode(', ', $allowedExt)) ?>; max <?= $maxMb ?> MB)</label><input type="file" name="document" class="form-control" required></div>
 </div><button class="btn btn-primary mt-3">Upload submission</button></form><?php endif; ?>
</div></div>
<div class="card"><div class="card-header d-flex justify-content-between align-items-center">Submission history
 <input type="search" class="form-control form-control-sm w-auto" placeholder="Search..." data-table-search="#hist"></div>
<div class="table-responsive"><table class="table table-hover mb-0 align-middle" id="hist"><thead><tr><th>Date</th><th>Milestone</th><th>Title</th><th>File</th><th>Status</th></tr></thead><tbody>
<?php foreach ($hist as $h): ?><tr><td><?= e(date('d M Y H:i', strtotime($h['submitted_at']))) ?></td><td><?= (int)$h['milestone_no'] ?>. <?= e($h['mtitle']) ?></td><td><?= e($h['title']) ?></td>
 <td><?php if ($h['file_path']): ?><a href="<?= e(url('download.php?id=' . (int)$h['id'])) ?>"><?= e($h['file_name']) ?></a><?php else: ?>—<?php endif; ?></td><td><?= badge($h['status']) ?></td></tr>
<?php endforeach; if (!$hist): ?><tr><td colspan="5" class="text-muted">No submissions yet.</td></tr><?php endif; ?></tbody></table></div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
