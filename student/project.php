<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('student');
$stu = student_rec(); $proj = student_project((int)$stu['id']);
$pageTitle = 'My Project'; include __DIR__ . '/../includes/header.php';
if (!$proj): ?><div class="card p-4 text-muted">No project has been assigned to you yet.</div>
<?php else: ?>
<div class="row g-3">
<div class="col-lg-8"><div class="card"><div class="card-header">Project details</div><div class="card-body">
  <h4><?= e($proj['title']) ?></h4><p><?= nl2br(e($proj['description'])) ?></p>
  <dl class="row mb-0">
   <dt class="col-sm-4">Status</dt><dd class="col-sm-8"><?= badge($proj['status']) ?></dd>
   <dt class="col-sm-4">Overall progress</dt><dd class="col-sm-8"><?= progress_bar((int)$proj['progress']) ?></dd>
   <dt class="col-sm-4">Academic session</dt><dd class="col-sm-8"><?= e(setting('academic_session', '—')) ?></dd>
   <dt class="col-sm-4">Created</dt><dd class="col-sm-8"><?= e(fdate($proj['created_at'])) ?></dd>
  </dl></div></div></div>
<div class="col-lg-4"><div class="card"><div class="card-header">Supervisor</div><div class="card-body">
  <?php if ($proj['supervisor_name']): ?><h6><?= e($proj['supervisor_name']) ?></h6><div class="text-muted"><?= e($proj['supervisor_dept']) ?></div><a href="mailto:<?= e($proj['supervisor_email']) ?>"><?= e($proj['supervisor_email']) ?></a>
  <?php else: ?><span class="text-muted">Not yet assigned.</span><?php endif; ?></div></div></div>
</div>
<?php endif; include __DIR__ . '/../includes/footer.php'; ?>
