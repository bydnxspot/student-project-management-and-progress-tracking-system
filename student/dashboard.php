<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('student');
$stu = student_rec(); $proj = student_project((int)$stu['id']);
$pageTitle = 'Student Dashboard'; include __DIR__ . '/../includes/header.php';
if (!$proj): ?>
<div class="card p-4"><h5>Welcome, <?= e($stu['full_name']) ?></h5><p class="mb-0 text-muted">No project has been assigned to you yet. The administrator will assign your supervisor and project topic shortly.</p></div>
<?php include __DIR__ . '/../includes/footer.php'; exit; endif;
$ms = db()->prepare('SELECT * FROM project_milestones WHERE project_id=? ORDER BY milestone_no'); $ms->execute([$proj['id']]); $ms = $ms->fetchAll();
$cnt = ['Approved'=>0,'Pending'=>0,'Revision Required'=>0];
$q = db()->prepare("SELECT status, COUNT(*) c FROM submissions WHERE student_id=? GROUP BY status"); $q->execute([$stu['id']]);
foreach ($q->fetchAll() as $r) { if ($r['status']==='Approved') $cnt['Approved']+=$r['c']; elseif (in_array($r['status'],['Pending','Under Review'])) $cnt['Pending']+=$r['c']; else $cnt['Revision Required']+=$r['c']; }
$fb = db()->prepare('SELECT f.*, u.full_name, m.title mtitle FROM supervisor_feedback f JOIN project_milestones m ON m.id=f.milestone_id JOIN supervisors s ON s.id=f.supervisor_id JOIN users u ON u.id=s.user_id WHERE m.project_id=? ORDER BY f.created_at DESC LIMIT 3'); $fb->execute([$proj['id']]); $fb = $fb->fetchAll();
$next = array_values(array_filter($ms, fn($m) => $m['status'] !== 'Approved' && $m['due_date'] >= date('Y-m-d')));
$overdue = array_filter($ms, fn($m) => $m['status'] !== 'Approved' && $m['due_date'] < date('Y-m-d'));
?>
<div class="row g-3 mb-3">
  <div class="col-6 col-lg-3"><div class="card stat"><div class="num"><?= (int)$proj['progress'] ?>%</div><div class="lbl">Overall progress</div></div></div>
  <div class="col-6 col-lg-3"><div class="card stat"><div class="num"><?= $cnt['Approved'] ?></div><div class="lbl">Approved submissions</div></div></div>
  <div class="col-6 col-lg-3"><div class="card stat"><div class="num"><?= $cnt['Pending'] ?></div><div class="lbl">Pending review</div></div></div>
  <div class="col-6 col-lg-3"><div class="card stat"><div class="num"><?= $cnt['Revision Required'] ?></div><div class="lbl">Need revision</div></div></div>
</div>
<?php if ($overdue): ?><div class="alert alert-warning">You have <?= count($overdue) ?> overdue milestone(s): <?= e(implode('; ', array_map(fn($m) => $m['title'], $overdue))) ?>.</div><?php endif; ?>
<div class="row g-3">
 <div class="col-lg-7">
  <div class="card mb-3"><div class="card-header">My Project</div><div class="card-body">
    <h5><?= e($proj['title']) ?></h5>
    <p class="text-muted mb-2">Supervisor: <strong><?= e($proj['supervisor_name'] ?? 'Not assigned') ?></strong></p>
    <?= progress_bar((int)$proj['progress']) ?>
    <a class="btn btn-sm btn-primary mt-3" href="<?= e(url('student/upload.php')) ?>">Submit work</a>
    <a class="btn btn-sm btn-outline-primary mt-3" href="<?= e(url('student/milestones.php')) ?>">View milestones</a>
  </div></div>
  <div class="card"><div class="card-header">Latest supervisor feedback</div><ul class="list-group list-group-flush">
   <?php foreach ($fb as $f): ?><li class="list-group-item"><div class="d-flex justify-content-between"><strong><?= e($f['mtitle']) ?></strong><?= badge($f['decision']) ?></div><div><?= e($f['comment']) ?></div><small class="text-muted"><?= e($f['full_name']) ?> &middot; <?= e(date('d M Y', strtotime($f['created_at']))) ?></small></li><?php endforeach; if (!$fb): ?><li class="list-group-item text-muted">No feedback yet.</li><?php endif; ?>
  </ul></div>
 </div>
 <div class="col-lg-5"><div class="card"><div class="card-header">Milestone timeline</div><div class="card-body">
  <ul class="timeline">
  <?php foreach ($ms as $m): $cls = ['Approved'=>'s-approved','In Progress'=>'s-progress','Submitted'=>'s-submitted','Under Review'=>'s-review','Revision Required'=>'s-revision'][$m['status']] ?? ''; ?>
   <li class="<?= $cls ?>"><strong><?= (int)$m['milestone_no'] ?>. <?= e($m['title']) ?></strong> <?= badge($m['status']) ?><br><small class="text-muted">Due <?= e(fdate($m['due_date'])) ?> &middot; <?= (int)$m['completion'] ?>%</small></li>
  <?php endforeach; ?></ul></div></div></div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
