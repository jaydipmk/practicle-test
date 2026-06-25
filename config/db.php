<?php
// config/db.php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'incident_system');

function getDB()
{
    static $conn = null;

    if ($conn === null) {
        $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if (!$conn) {
            error_log('DB Connection Failed: ' . mysqli_connect_error());
            die(json_encode([
                'success' => false,
                'message' => 'Database connection failed.'
            ]));
        }

        mysqli_set_charset($conn, 'utf8mb4');
    }

    return $conn;
}