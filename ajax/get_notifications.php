<?php
// ajax/get_notifications.php

require_once __DIR__ . '/../middleware/auth.php';
requireLogin('user');

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

$uid = authId();
$db  = getDB();

// Get unread count
$result = mysqli_query($db, "
    SELECT COUNT(*) as cnt
    FROM notifications
    WHERE user_id = '$uid' AND is_read = 0
");

$row   = mysqli_fetch_assoc($result);
$count = (int)$row['cnt'];

// Get latest unread notifications
$notifications = [];
$res = mysqli_query($db, "
    SELECT n.id, n.message, n.created_at, n.is_read,
           i.title as incident_title, i.status as incident_status
    FROM notifications n
    JOIN incidents i ON i.id = n.incident_id
    WHERE n.user_id = '$uid' AND n.is_read = 0
    ORDER BY n.created_at DESC
    LIMIT 10
");

while ($row = mysqli_fetch_assoc($res)) {
    $notifications[] = [
        'id'               => (int)$row['id'],
        'message'          => $row['message'],
        'incident_title'   => $row['incident_title'],
        'incident_status'  => $row['incident_status'],
        'created_at'       => date('d M Y H:i', strtotime($row['created_at'])),
    ];
}

echo json_encode([
    'success'       => true,
    'count'         => $count,
    'notifications' => $notifications,
]);