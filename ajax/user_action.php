<?php

require_once __DIR__ . '/../middleware/auth.php';
requireLogin('superadmin');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/audit.php';

header('Content-Type: application/json');

$db     = getDB();
$action = trim($_POST['action'] ?? '');

switch ($action) {

    // ── Add User ──
    case 'add':
        $name     = trim($_POST['name']     ?? '');
        $email    = trim($_POST['email']    ?? '');
        $password = trim($_POST['password'] ?? '');
        $role     = trim($_POST['role']     ?? 'user');

        if (empty($name) || empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'All fields required.']);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email.']);
            exit;
        }

        if (strlen($password) < 6) {
            echo json_encode(['success' => false, 'message' => 'Password min 6 characters.']);
            exit;
        }

        $allowedRoles = ['user','admin','superadmin'];
        if (!in_array($role, $allowedRoles)) {
            echo json_encode(['success' => false, 'message' => 'Invalid role.']);
            exit;
        }

        $emailEsc = mysqli_real_escape_string($db, $email);

        // Check duplicate
        $check = mysqli_query($db, "SELECT id FROM users WHERE email = '$emailEsc' LIMIT 1");
        if (mysqli_num_rows($check) > 0) {
            echo json_encode(['success' => false, 'message' => 'Email already exists.']);
            exit;
        }

        $nameEsc  = mysqli_real_escape_string($db, $name);
        $roleEsc  = mysqli_real_escape_string($db, $role);
        $hash     = password_hash($password, PASSWORD_BCRYPT);
        $hashEsc  = mysqli_real_escape_string($db, $hash);

        $sql = "INSERT INTO users (name, email, password, role)
                VALUES ('$nameEsc', '$emailEsc', '$hashEsc', '$roleEsc')";

        if (!mysqli_query($db, $sql)) {
            echo json_encode(['success' => false, 'message' => 'Failed to add user.']);
            exit;
        }

        $newId = (int)mysqli_insert_id($db);
        AuditLog::write(authId(), 'Added User', 'user', $newId, ['email' => $email, 'role' => $role]);

        echo json_encode(['success' => true, 'message' => 'User added successfully.']);
        break;

    // ── Edit User ──
    case 'edit':
        $id       = (int)($_POST['id']       ?? 0);
        $name     = trim($_POST['name']      ?? '');
        $email    = trim($_POST['email']     ?? '');
        $password = trim($_POST['password']  ?? '');
        $role     = trim($_POST['role']      ?? '');

        if ($id <= 0 || empty($name) || empty($email)) {
            echo json_encode(['success' => false, 'message' => 'Name and email required.']);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email.']);
            exit;
        }

        $allowedRoles = ['user','admin','superadmin'];
        if (!in_array($role, $allowedRoles)) {
            echo json_encode(['success' => false, 'message' => 'Invalid role.']);
            exit;
        }

        $emailEsc = mysqli_real_escape_string($db, $email);

        // Check duplicate email
        $check = mysqli_query($db, "
            SELECT id FROM users
            WHERE email = '$emailEsc' AND id != '$id'
            LIMIT 1
        ");
        if (mysqli_num_rows($check) > 0) {
            echo json_encode(['success' => false, 'message' => 'Email already used by another user.']);
            exit;
        }

        $nameEsc = mysqli_real_escape_string($db, $name);
        $roleEsc = mysqli_real_escape_string($db, $role);

        // Build password update part
        $passwordSql = '';
        if (!empty($password)) {
            if (strlen($password) < 6) {
                echo json_encode(['success' => false, 'message' => 'Password min 6 characters.']);
                exit;
            }
            $hash        = password_hash($password, PASSWORD_BCRYPT);
            $hashEsc     = mysqli_real_escape_string($db, $hash);
            $passwordSql = ", password = '$hashEsc'";
        }

        $sql = "UPDATE users
                SET name  = '$nameEsc',
                    email = '$emailEsc',
                    role  = '$roleEsc'
                    $passwordSql
                WHERE id = '$id'";

        if (!mysqli_query($db, $sql)) {
            echo json_encode(['success' => false, 'message' => 'Update failed.']);
            exit;
        }

        AuditLog::write(authId(), 'Edited User', 'user', $id, ['email' => $email, 'role' => $role]);

        echo json_encode(['success' => true, 'message' => 'User updated successfully.']);
        break;

    // ── Block User ──
    case 'block':
        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0 || $id === authId()) {
            echo json_encode(['success' => false, 'message' => 'Cannot block yourself.']);
            exit;
        }

        mysqli_query($db, "UPDATE users SET is_blocked = 1 WHERE id = '$id'");

        AuditLog::write(authId(), 'Blocked User', 'user', $id);

        echo json_encode(['success' => true, 'message' => 'User blocked.']);
        break;

    // ── Unblock User ──
    case 'unblock':
        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid user ID.']);
            exit;
        }

        mysqli_query($db, "UPDATE users SET is_blocked = 0 WHERE id = '$id'");

        AuditLog::write(authId(), 'Unblocked User', 'user', $id);

        echo json_encode(['success' => true, 'message' => 'User unblocked.']);
        break;

    // ── Delete User ──
    case 'delete':
        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0 || $id === authId()) {
            echo json_encode(['success' => false, 'message' => 'Cannot delete yourself.']);
            exit;
        }

        // Delete evidence files for this user's incidents
        $files = mysqli_query($db, "
            SELECT evidence_path FROM incidents
            WHERE user_id = '$id' AND evidence_path IS NOT NULL
        ");
        while ($row = mysqli_fetch_assoc($files)) {
            $path = UPLOAD_DIR . $row['evidence_path'];
            if (file_exists($path)) unlink($path);
        }

        // Delete user
        mysqli_query($db, "DELETE FROM users WHERE id = '$id'");

        AuditLog::write(authId(), 'Deleted User', 'user', $id);

        echo json_encode(['success' => true, 'message' => 'User deleted successfully.']);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        break;
}