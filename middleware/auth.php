<?php
// middleware/auth.php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/app.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireLogin($roles = null)
{
    // Not logged in
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }

    $userId = (int)$_SESSION['user_id'];
    $db     = getDB();

    // Fresh DB check for block status + role
    $result = mysqli_query($db, "SELECT is_blocked, role FROM users WHERE id = '$userId' LIMIT 1");
    $user   = $result ? mysqli_fetch_assoc($result) : null;

    if (!$user || (int)$user['is_blocked'] === 1) {
        session_unset();
        session_destroy();
        header('Location: ' . BASE_URL . '/auth/login.php?blocked=1');
        exit;
    }

    // Sync role
    $_SESSION['role'] = $user['role'];

    // Role check
    if ($roles !== null) {
        $allowed = is_array($roles) ? $roles : [$roles];
        if (!in_array($_SESSION['role'], $allowed)) {
            $role = $_SESSION['role'];
            if ($role === 'superadmin')    header('Location: ' . BASE_URL . '/superadmin/dashboard.php');
            elseif ($role === 'admin')     header('Location: ' . BASE_URL . '/admin/dashboard.php');
            else                           header('Location: ' . BASE_URL . '/user/dashboard.php');
            exit;
        }
    }
}

function authId()   { return (int)($_SESSION['user_id'] ?? 0); }
function authRole() { return $_SESSION['role'] ?? ''; }
function authName() { return $_SESSION['name'] ?? ''; }

function hasRole($role)           { return ($_SESSION['role'] ?? '') === $role; }
function hasAnyRole(array $roles) { return in_array($_SESSION['role'] ?? '', $roles); }