<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
$sql = "SELECT p.id, u.full_name student, s.matric_no, s.department, p.title, COALESCE(su.full_name,'Unassigned') supervisor, p.progress, p.status,
  (SELECT COUNT(*) FROM project_milestones m WHERE m.project_id=p.id AND m.status='Approved') approved_ms,
  (SELECT COUNT(*) FROM submissions x WHERE x.project_id=p.id) subs,
  (SELECT COUNT(*) FROM submissions x WHERE x.project_id=p.id AND x.status IN ('Pending','Under Review')) pending
  FROM projects p JOIN students s ON s.id=p.student_id JOIN users u ON u.id=s.user_id LEFT JOIN supervisors sp ON sp.id=p.supervisor_id LEFT JOIN users su ON su.id=sp.user_id ORDER BY u.full_name";
$rows = db()->query($sql)->fetchAll();
if (isset($_GET['export'])) {
    log_activity('Report export', 'Exported project progress report to CSV');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="project_progress_report_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w'); fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Student', 'Matric No', 'Department', 'Project Title', 'Supervisor', 'Progress %', 'Status', 'Milestones Approved (of 7)', 'Submissions', 'Pending Reviews']);
    foreach ($rows as $r) {
        // Neutralise spreadsheet formula injection
        $cells = [$r['student'], $r['matric_no'], $r['department'], $r['title'], $r['supervisor']];
        $cells = array_map(fn($c) => preg_match('/^[=+\-@]/', (string)$c) ? "'" . $c : $c, $cells);
        fputcsv($out, array_merge($cells, [$r['progress'], $r['status'], $r['approved_ms'], $r['subs'], $r['pending']]));
    }
    fclose($out); exit;
}
$total = count($rows); $done = count(array_filter($rows, fn($r) => $r['status'] === 'Completed'));
$avg = $total ? round(array_sum(array_column($rows, 'progress')) / $total) : 0;
$bySup = db()->query("SELECT u.full_name, COUNT(p.id) n, COALESCE(ROUND(AVG(p.progress)),0) avg FROM supervisors sp JOIN users u ON u.id=sp.user_id LEFT JOIN projects p ON p.supervisor_id=sp.id GROUP BY sp.id, u.full_name ORDER BY u.full_name")->fetchAll();
$pageTitle = 'Reports'; include __DIR__ . '/../includes/header.php';
?>
<div class="row g-3 mb-3">
 <div class="col-md-4"><div class="card stat"><div class="num"><?= $total ?></div><div class="lbl">Total projects</div></div></div>
 <div class="col-md-4"><div class="card stat"><div class="num"><?= $done ?></div><div class="lbl">Completed</div></div></div>
 <div class="col-md-4"><div class="card stat"><div class="num"><?= $avg ?>%</div><div class="lbl">Average completion</div></div></div>
</div>
<div class="card mb-3"><div class="card-header">Workload by supervisor</div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Supervisor</th><th>Projects</th><th>Average progress</th></tr></thead><tbody>
<?php foreach ($bySup as $s): ?><tr><td><?= e($s['full_name']) ?></td><td><?= (int)$s['n'] ?></td><td style="width:240px"><?= progress_bar((int)$s['avg']) ?></td></tr><?php endforeach; ?></tbody></table></div></div>
<div class="card"><div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">Project progress report
 <div class="d-flex gap-2"><input type="search" class="form-control form-control-sm" placeholder="Search..." data-table-search="#t">
 <select class="form-select form-select-sm" data-table-filter="#t" data-col="5"><option value="">All status</option><option>Not Started</option><option>In Progress</option><option>Completed</option></select>
 <a class="btn btn-sm btn-primary text-nowrap" href="<?= e(url('admin/reports.php?export=1')) ?>">Export CSV</a></div></div>
<div class="table-responsive"><table class="table mb-0 align-middle" id="t"><thead><tr><th>Student</th><th>Matric No</th><th>Project</th><th>Supervisor</th><th>Progress</th><th>Status</th><th>Approved</th><th>Pending</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><?= e($r['student']) ?></td><td><?= e($r['matric_no']) ?></td><td><?= e($r['title']) ?></td><td><?= e($r['supervisor']) ?></td><td style="width:140px"><?= progress_bar((int)$r['progress']) ?></td><td><?= badge($r['status']) ?></td><td><?= (int)$r['approved_ms'] ?>/7</td><td><?= (int)$r['pending'] ?></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="8" class="text-muted">No projects yet.</td></tr><?php endif; ?></tbody></table></div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
