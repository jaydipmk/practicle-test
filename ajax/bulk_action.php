<?php

require_once __DIR__ . '/../middleware/auth.php';
requireLogin(['admin', 'superadmin']);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/audit.php';

header('Content-Type: application/json');

$db     = getDB();
$action = trim($_POST['action'] ?? '');
$ids    = $_POST['ids']         ?? [];

// ── Validate ──
if (empty($ids) || !is_array($ids)) {
    echo json_encode(['success' => false, 'message' => 'No incidents selected.']);
    exit;
}

// only integers allowed
$cleanIds = [];
foreach ($ids as $id) {
    $clean = (int)$id;
    if ($clean > 0) $cleanIds[] = $clean;
}

if (empty($cleanIds)) {
    echo json_encode(['success' => false, 'message' => 'Invalid incident IDs.']);
    exit;
}

$idList = implode(',', $cleanIds);

// Actions 
switch ($action) {

    // Bulk Resolve
    case 'resolve':
        // Get user IDs for notifications
        $userResult = mysqli_query($db, "
            SELECT id, user_id, status FROM incidents
            WHERE id IN ($idList) AND status != 'Resolved'
        ");

        $notifyList = [];
        while ($row = mysqli_fetch_assoc($userResult)) {
            $notifyList[] = $row;
        }

        // Update status
        $sql = "UPDATE incidents
                SET status = 'Resolved', resolved_at = NOW()
                WHERE id IN ($idList) AND status != 'Resolved'";

        if (!mysqli_query($db, $sql)) {
            echo json_encode(['success' => false, 'message' => 'Bulk resolve failed.']);
            exit;
        }

        $affected = (int)mysqli_affected_rows($db);

        // Send notifications
        foreach ($notifyList as $inc) {
            $uid     = (int)$inc['user_id'];
            $incId   = (int)$inc['id'];
            $message = mysqli_real_escape_string($db, 'Your incident has been marked as Resolved.');
            mysqli_query($db, "
                INSERT INTO notifications (user_id, incident_id, message)
                VALUES ('$uid', '$incId', '$message')
            ");
        }

        // Audit
        AuditLog::write(
            authId(),
            'Bulk Resolved Incidents',
            'incident',
            null,
            ['ids' => $cleanIds]
        );

        echo json_encode([
            'success' => true,
            'message' => "$affected incident(s) marked as Resolved."
        ]);
        break;

    //  Bulk Assign
    case 'assign':
        $assignTo = (int)($_POST['assign_to'] ?? 0);

        if ($assignTo <= 0) {
            echo json_encode(['success' => false, 'message' => 'Please select an admin.']);
            exit;
        }

        // Verify admin exists
        $adminCheck = mysqli_query($db, "
            SELECT id FROM users
            WHERE id = '$assignTo'
              AND role IN ('admin','superadmin')
            LIMIT 1
        ");

        if (!$adminCheck || mysqli_num_rows($adminCheck) === 0) {
            echo json_encode(['success' => false, 'message' => 'Selected admin not found.']);
            exit;
        }

        $sql = "UPDATE incidents
                SET assigned_to = '$assignTo'
                WHERE id IN ($idList)";

        if (!mysqli_query($db, $sql)) {
            echo json_encode(['success' => false, 'message' => 'Bulk assign failed.']);
            exit;
        }

        $affected = (int)mysqli_affected_rows($db);

        // Audit
        AuditLog::write(
            authId(),
            'Bulk Assigned Incidents',
            'incident',
            null,
            ['ids' => $cleanIds, 'assigned_to' => $assignTo]
        );

        echo json_encode([
            'success' => true,
            'message' => "$affected incident(s) assigned successfully."
        ]);
        break;

    // Bulk Delete
    case 'delete':
        if (!hasRole('superadmin')) {
            echo json_encode(['success' => false, 'message' => 'Only Super Admin can delete incidents.']);
            exit;
        }

        // Delete evidence files first
        $fileResult = mysqli_query($db, "
            SELECT evidence_path FROM incidents
            WHERE id IN ($idList) AND evidence_path IS NOT NULL
        ");

        while ($row = mysqli_fetch_assoc($fileResult)) {
            $filePath = UPLOAD_DIR . $row['evidence_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        // Delete incidents
        $sql = "DELETE FROM incidents WHERE id IN ($idList)";

        if (!mysqli_query($db, $sql)) {
            echo json_encode(['success' => false, 'message' => 'Delete failed.']);
            exit;
        }

        $affected = (int)mysqli_affected_rows($db);

        // Audit
        AuditLog::write(
            authId(),
            'Bulk Deleted Incidents',
            'incident',
            null,
            ['ids' => $cleanIds]
        );

        echo json_encode([
            'success' => true,
            'message' => "$affected incident(s) deleted permanently."
        ]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        break;
}