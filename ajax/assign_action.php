<?php

require_once __DIR__ . '/../middleware/auth.php';
requireLogin(['admin', 'superadmin']);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/audit.php';

header('Content-Type: application/json');

$db     = getDB();
$action = trim($_POST['action'] ?? '');
$id     = (int)($_POST['id']    ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid incident ID.']);
    exit;
}

// Check incident exists
$check = mysqli_query($db, "SELECT id FROM incidents WHERE id = '$id' LIMIT 1");
if (!$check || mysqli_num_rows($check) === 0) {
    echo json_encode(['success' => false, 'message' => 'Incident not found.']);
    exit;
}

if ($action === 'assign') {

    $assignTo = (int)($_POST['assign_to'] ?? 0);

    if ($assignTo <= 0) {
        echo json_encode(['success' => false, 'message' => 'Please select an admin.']);
        exit;
    }

    // Verify admin
    $adminCheck = mysqli_query($db, "
        SELECT id FROM users
        WHERE id = '$assignTo' AND role IN ('admin','superadmin')
        LIMIT 1
    ");
    if (!$adminCheck || mysqli_num_rows($adminCheck) === 0) {
        echo json_encode(['success' => false, 'message' => 'Admin not found.']);
        exit;
    }

    mysqli_query($db, "
        UPDATE incidents SET assigned_to = '$assignTo' WHERE id = '$id'
    ");

    AuditLog::write(
        authId(),
        'Assigned Incident',
        'incident',
        $id,
        ['assigned_to' => $assignTo]
    );

    echo json_encode(['success' => true, 'message' => 'Incident assigned successfully.']);

} elseif ($action === 'unassign') {

    mysqli_query($db, "UPDATE incidents SET assigned_to = NULL WHERE id = '$id'");

    AuditLog::write(authId(), 'Unassigned Incident', 'incident', $id);

    echo json_encode(['success' => true, 'message' => 'Incident unassigned.']);

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}