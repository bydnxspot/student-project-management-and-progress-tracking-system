<?php
require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

const MILESTONE_STATUSES = ['Not Started', 'In Progress', 'Submitted', 'Under Review', 'Approved', 'Revision Required'];

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function url(string $p = ''): string { return BASE_URL . '/' . ltrim($p, '/'); }
function redirect(string $p): void { header('Location: ' . url($p)); exit; }

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function csrf_check(): void {
    $t = $_POST['csrf'] ?? '';
    if (!is_string($t) || !hash_equals($_SESSION['csrf'] ?? '', $t)) {
        http_response_code(403);
        exit('Invalid or expired form token. Go back, refresh the page and try again.');
    }
}

function flash(string $msg, string $type = 'success'): void { $_SESSION['flash'][] = [$msg, $type]; }
function show_flash(): void {
    foreach ($_SESSION['flash'] ?? [] as [$m, $t]) echo '<div class="alert alert-' . e($t) . ' alert-dismissible fade show" role="alert">' . e($m) . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    unset($_SESSION['flash']);
}

function is_logged_in(): bool { return !empty($_SESSION['user_id']); }
function require_login(): void { if (!is_logged_in()) redirect('login.php'); }
function require_role(string $role): void {
    require_login();
    if (($_SESSION['role'] ?? '') !== $role) { http_response_code(403); exit('Access denied. You do not have permission to view this page. <a href="' . e(url('index.php')) . '">Go to your dashboard</a>'); }
    $st = db()->prepare('SELECT status FROM users WHERE id=?'); $st->execute([$_SESSION['user_id']]);
    if ($st->fetchColumn() !== 'active') { logout_now(); header('Location: ' . url('login.php?msg=inactive')); exit; }
}
function logout_now(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) { $p = session_get_cookie_params(); setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']); }
    session_destroy();
}
function home_for(string $role): string { return $role . '/dashboard.php'; }

function uid(): int { return (int)($_SESSION['user_id'] ?? 0); }
function student_rec(): array {
    $st = db()->prepare('SELECT s.*, u.full_name, u.email FROM students s JOIN users u ON u.id=s.user_id WHERE s.user_id=?');
    $st->execute([uid()]); $r = $st->fetch();
    if (!$r) { exit('Student record not found.'); } return $r;
}
function supervisor_rec(): array {
    $st = db()->prepare('SELECT s.*, u.full_name, u.email FROM supervisors s JOIN users u ON u.id=s.user_id WHERE s.user_id=?');
    $st->execute([uid()]); $r = $st->fetch();
    if (!$r) { exit('Supervisor record not found.'); } return $r;
}
function student_project(int $studentId): ?array {
    $st = db()->prepare('SELECT p.*, su.full_name AS supervisor_name, su.email AS supervisor_email, sp.department AS supervisor_dept FROM projects p LEFT JOIN supervisors sp ON sp.id=p.supervisor_id LEFT JOIN users su ON su.id=sp.user_id WHERE p.student_id=?');
    $st->execute([$studentId]); return $st->fetch() ?: null;
}

function log_activity(string $action, string $details = ''): void {
    $st = db()->prepare('INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?,?,?,?)');
    $st->execute([$_SESSION['user_id'] ?? null, $action, $details, $_SERVER['REMOTE_ADDR'] ?? '']);
}
function notify(int $userId, string $message, string $link = ''): void {
    db()->prepare('INSERT INTO notifications (user_id, message, link) VALUES (?,?,?)')->execute([$userId, $message, $link]);
}
function unread_count(): int {
    $st = db()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0'); $st->execute([uid()]); return (int)$st->fetchColumn();
}

/** Overall progress = average completion % across the project's milestones. */
function recalc_progress(int $projectId): int {
    $st = db()->prepare('SELECT COALESCE(ROUND(AVG(completion)),0) FROM project_milestones WHERE project_id=?'); $st->execute([$projectId]);
    $p = (int)$st->fetchColumn();
    $status = $p >= 100 ? 'Completed' : ($p > 0 ? 'In Progress' : 'Not Started');
    db()->prepare('UPDATE projects SET progress=?, status=? WHERE id=?')->execute([$p, $status, $projectId]);
    return $p;
}

function badge(string $s): string {
    $map = ['Not Started' => 'secondary', 'In Progress' => 'primary', 'Submitted' => 'info', 'Under Review' => 'warning', 'Approved' => 'success',
            'Revision Required' => 'danger', 'Pending' => 'info', 'Rejected' => 'danger', 'Completed' => 'success', 'active' => 'success', 'inactive' => 'secondary'];
    return '<span class="badge text-bg-' . ($map[$s] ?? 'secondary') . '">' . e($s) . '</span>';
}
function progress_bar(int $p): string {
    return '<div class="progress" style="height:10px"><div class="progress-bar" style="width:' . $p . '%"></div></div><small class="text-muted">' . $p . '%</small>';
}
function setting(string $key, string $default = ''): string {
    $st = db()->prepare('SELECT setting_value FROM academic_settings WHERE setting_key=?'); $st->execute([$key]);
    $v = $st->fetchColumn(); return $v === false ? $default : (string)$v;
}
function fdate(?string $d): string { return $d ? date('d M Y', strtotime($d)) : '—'; }

/** Create the seven standard milestones for a project, spacing due dates from the settings start date. */
function create_milestones(int $projectId): void {
    $start = setting('session_start_date', date('Y-m-d'));
    $defs = [
        [1, 'Topic Selection and Approval', 'Select a viable project topic and obtain supervisor approval.', 0, 35],
        [2, 'Proposal Preparation', 'Prepare and submit the project proposal with problem statement, aim and objectives.', 36, 56],
        [3, 'Literature Review', 'Review related works and existing systems relevant to the project.', 57, 95],
        [4, 'System Analysis and Design', 'Analyse requirements and produce system design (DFDs, ERD, UI designs).', 96, 155],
        [5, 'Implementation', 'Develop and implement the system according to the approved design.', 156, 210],
        [6, 'Testing', 'Test the system, document results and fix defects.', 211, 245],
        [7, 'Final Documentation and Submission', 'Compile and submit the final project report and source code.', 246, 280],
    ];
    $ins = db()->prepare('INSERT INTO project_milestones (project_id, milestone_no, title, description, start_date, due_date, status, completion) VALUES (?,?,?,?,?,?,?,0)');
    foreach ($defs as [$n, $t, $d, $a, $b]) {
        $ins->execute([$projectId, $n, $t, $d, date('Y-m-d', strtotime("$start +$a days")), date('Y-m-d', strtotime("$start +$b days")), 'Not Started']);
    }
}
