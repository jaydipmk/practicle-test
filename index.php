<?php
// index.php
// Root entry point — redirect based on session role

require_once __DIR__ . '/config/app.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$role = $_SESSION['role'] ?? 'user';

if ($role === 'superadmin') {
    header('Location: ' . BASE_URL . '/superadmin/dashboard.php');
} elseif ($role === 'admin') {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
} else {
    header('Location: ' . BASE_URL . '/user/dashboard.php');
}
exit;