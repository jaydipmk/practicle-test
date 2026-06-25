<?php
// auth/logout.php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/audit.php';

session_start();

$userId = $_SESSION['user_id'] ?? null;
if ($userId) {
    AuditLog::write((int)$userId, 'User Logout', 'user', (int)$userId);
}

session_unset();
session_destroy();

header('Location: ' . BASE_URL . '/auth/login.php');
exit;