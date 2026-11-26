<?php
// Permission-checked file download. Uploaded files are not directly web-accessible.
require_once __DIR__ . '/includes/auth.php';
require_login();
$id = (int)($_GET['id'] ?? 0);
$st = db()->prepare('SELECT sub.*, p.supervisor_id, s.user_id AS student_user, sp.user_id AS sup_user
    FROM submissions sub JOIN projects p ON p.id=sub.project_id JOIN students s ON s.id=sub.student_id LEFT JOIN supervisors sp ON sp.id=p.supervisor_id WHERE sub.id=?');
$st->execute([$id]); $f = $st->fetch();
$allowed = $f && ($_SESSION['role'] === 'admin' || ($_SESSION['role'] === 'student' && (int)$f['student_user'] === uid()) || ($_SESSION['role'] === 'supervisor' && (int)$f['sup_user'] === uid()));
if (!$allowed || !$f['file_path']) { http_response_code(403); exit('File not available or access denied.'); }
$path = realpath(UPLOAD_DIR . basename($f['file_path']));
$base = realpath(UPLOAD_DIR);
if (!$path || !$base || strpos($path, $base) !== 0 || !is_file($path)) { http_response_code(404); exit('File not found on server.'); }
log_activity('Download', 'Downloaded submission #' . $id);
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . str_replace(['"', "\r", "\n"], '', $f['file_name']) . '"');
header('Content-Length: ' . filesize($path));
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;
