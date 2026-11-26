<?php
require_once __DIR__ . '/includes/auth.php';
if (is_logged_in()) log_activity('Logout', 'User logged out');
logout_now();
header('Location: ' . url('login.php?msg=loggedout'));
exit;
