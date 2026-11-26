<?php
$role = $_SESSION['role'] ?? '';
$menus = [
 'admin' => [['admin/dashboard.php','Dashboard'],['admin/users.php','Users'],['admin/user_add.php','Add User'],['admin/projects.php','Projects'],['admin/project_add.php','Create Project'],['admin/reports.php','Reports'],['admin/logs.php','Activity Logs'],['admin/settings.php','Settings']],
 'student' => [['student/dashboard.php','Dashboard'],['student/project.php','My Project'],['student/milestones.php','Milestones'],['student/upload.php','Submit / Upload'],['student/progress.php','Progress & History'],['student/feedback.php','Supervisor Feedback'],['student/profile.php','My Profile']],
 'supervisor' => [['supervisor/dashboard.php','Dashboard'],['supervisor/students.php','My Students'],['supervisor/submissions.php','Submissions'],['supervisor/progress.php','Student Progress'],['supervisor/feedback.php','Feedback']],
];
$current = ltrim(str_replace(BASE_URL, '', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)), '/');
?>
<aside class="sidebar" id="sidebar">
  <div class="brand"><div class="brand-mark">SP</div><div><strong>SPMPTS</strong><small>University Project Portal</small></div></div>
  <ul class="nav flex-column">
    <?php foreach ($menus[$role] ?? [] as [$href, $label]): ?>
      <li><a class="nav-link <?= $current === $href ? 'active' : '' ?>" href="<?= e(url($href)) ?>"><?= e($label) ?></a></li>
    <?php endforeach; ?>
    <li><a class="nav-link <?= $current === 'notifications.php' ? 'active' : '' ?>" href="<?= e(url('notifications.php')) ?>">Notifications</a></li>
  </ul>
</aside>
