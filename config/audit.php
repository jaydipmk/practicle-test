<?php
// config/audit.php

require_once __DIR__ . '/db.php';

class AuditLog
{
    public static function write($userId, $action, $targetType, $targetId = null, $details = null)
    {
        $db         = getDB();
        $ip         = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $action     = mysqli_real_escape_string($db, $action);
        $targetType = mysqli_real_escape_string($db, $targetType);
        $ip         = mysqli_real_escape_string($db, $ip);
        $userId     = $userId   ? (int)$userId   : 'NULL';
        $targetId   = $targetId ? (int)$targetId : 'NULL';

        if ($details) {
            $json    = mysqli_real_escape_string($db, json_encode($details));
            $jsonVal = "'$json'";
        } else {
            $jsonVal = 'NULL';
        }

        $sql = "INSERT INTO audit_logs
                    (user_id, action, target_type, target_id, details, ip_address)
                VALUES
                    ($userId, '$action', '$targetType', $targetId, $jsonVal, '$ip')";

        mysqli_query($db, $sql);
    }
}