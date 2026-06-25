<?php

require_once __DIR__ . '/../middleware/auth.php';
requireLogin(['admin', 'superadmin']);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/audit.php';

header('Content-Type: application/json');

$db = getDB();

$id       = (int)($_POST['id']       ?? 0);
$status   = trim($_POST['status']    ?? '');
$priority = trim($_POST['priority']  ?? '');
$category = trim($_POST['category']  ?? '');

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid incident ID.']);
    exit;
}

$allowedStatus   = ['Open', 'In Progress', 'Resolved'];
$allowedPriority = ['Low', 'Medium', 'High'];
$allowedCategory = ['Phishing','Malware','Ransomware','Unauthorized Access','DDoS','Data Breach','Social Engineering','Other'];

if (!in_array($status, $allowedStatus)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status.']);
    exit;
}

if (!in_array($priority, $allowedPriority)) {
    echo json_encode(['success' => false, 'message' => 'Invalid priority.']);
    exit;
}

if (!in_array($category, $allowedCategory)) {
    echo json_encode(['success' => false, 'message' => 'Invalid category.']);
    exit;
}

// Check incident exists 
$check = mysqli_query($db, "SELECT id, status, user_id FROM incidents WHERE id = '$id' LIMIT 1");
if (!$check || mysqli_num_rows($check) === 0) {
    echo json_encode(['success' => false, 'message' => 'Incident not found.']);
    exit;
}

$existing = mysqli_fetch_assoc($check);
$userId   = (int)$existing['user_id'];
$oldStatus = $existing['status'];

// Build update query
$statusEsc   = mysqli_real_escape_string($db, $status);
$priorityEsc = mysqli_real_escape_string($db, $priority);
$categoryEsc = mysqli_real_escape_string($db, $category);

// Set resolved_at if status changed to Resolved
$resolvedVal = '';
if ($status === 'Resolved' && $oldStatus !== 'Resolved') {
    $resolvedVal = ", resolved_at = NOW()";
} elseif ($status !== 'Resolved') {
    $resolvedVal = ", resolved_at = NULL";
}

$sql = "UPDATE incidents
        SET status   = '$statusEsc',
            priority = '$priorityEsc',
            category = '$categoryEsc'
            $resolvedVal
        WHERE id = '$id'";

if (!mysqli_query($db, $sql)) {
    echo json_encode(['success' => false, 'message' => 'Update failed.']);
    exit;
}

// ── Notify user if status changed ──
if ($status !== $oldStatus) {
    $message   = mysqli_real_escape_string($db, "Your incident status has been updated to: $status");
    mysqli_query($db, "
        INSERT INTO notifications (user_id, incident_id, message)
        VALUES ('$userId', '$id', '$message')
    ");
}

// ── Audit log ──
AuditLog::write(
    authId(),
    'Updated Incident',
    'incident',
    $id,
    ['status' => $status, 'priority' => $priority, 'category' => $category]
);

echo json_encode(['success' => true, 'message' => 'Incident updated successfully.']);