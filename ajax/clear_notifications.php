<?php

require_once __DIR__ . '/../middleware/auth.php';
requireLogin('user');

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

$uid = authId();
$db  = getDB();

mysqli_query($db, "DELETE FROM notifications WHERE user_id = '$uid'");

echo json_encode([
    'success' => true,
    'message' => 'All notifications cleared.'
]);