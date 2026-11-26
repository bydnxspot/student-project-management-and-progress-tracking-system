<?php
// Command-line helper: re-hashes the DEMO account passwords using PHP's own password_hash().
// Use only if demo logins fail after importing database.sql.
//   C:\xampp\php\php.exe tools\reset_demo_passwords.php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Run this script from the command line only.'); }
require __DIR__ . '/../config/db.php';
$map = ['admin' => 'Admin@123', 'supervisor' => 'Supervisor@123', 'student' => 'Student@123'];
$st = db()->prepare('UPDATE users SET password_hash=? WHERE role=?');
foreach ($map as $role => $pw) { $st->execute([password_hash($pw, PASSWORD_DEFAULT), $role]); echo "Reset $role accounts\n"; }
