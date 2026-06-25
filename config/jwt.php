<?php
// config/jwt.php

require_once __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

define('JWT_SECRET',         '');
define('JWT_ALGO',           'HS256');
define('JWT_EXPIRE_SECONDS', 3600);     // 1 hour
define('JWT_REFRESH_DAYS',   7);        // 7 days

class JWTHelper
{
    // --------------------------------------------------
    // Generate access token
    // --------------------------------------------------
    public static function generateToken($user)
    {
        $payload = [
            'iss'  => 'incident-system',
            'iat'  => time(),
            'exp'  => time() + JWT_EXPIRE_SECONDS,
            'uid'  => (int)$user['id'],
            'role' => $user['role'],
            'name' => $user['name'],
        ];

        return JWT::encode($payload, JWT_SECRET, JWT_ALGO);
    }

    // --------------------------------------------------
    // Decode & validate access token
    // --------------------------------------------------
    public static function validateToken($token)
    {
        try {
            return JWT::decode($token, new Key(JWT_SECRET, JWT_ALGO));
        } catch (Exception $e) {
            return null;
        }
    }

    // --------------------------------------------------
    // Generate refresh token
    // --------------------------------------------------
    public static function generateRefreshToken()
    {
        return bin2hex(random_bytes(32));
    }

    // --------------------------------------------------
    // Save refresh token to DB (simple mysqli)
    // --------------------------------------------------
    public static function saveRefreshToken($userId, $token)
    {
        $db      = getDB();
        $expires = date('Y-m-d H:i:s', strtotime('+' . JWT_REFRESH_DAYS . ' days'));

        $userId  = (int)$userId;
        $token   = mysqli_real_escape_string($db, $token);
        $expires = mysqli_real_escape_string($db, $expires);

        $sql = "INSERT INTO refresh_tokens (user_id, token, expires_at)
                VALUES ('$userId', '$token', '$expires')";

        mysqli_query($db, $sql);
    }

    // --------------------------------------------------
    // Verify refresh token from DB
    // --------------------------------------------------
    public static function verifyRefreshToken($token)
    {
        $db    = getDB();
        $token = mysqli_real_escape_string($db, $token);

        $sql    = "SELECT rt.*, u.id AS user_id, u.role, u.name, u.is_blocked
                   FROM refresh_tokens rt
                   JOIN users u ON u.id = rt.user_id
                   WHERE rt.token = '$token'
                     AND rt.expires_at > NOW()
                   LIMIT 1";

        $result = mysqli_query($db, $sql);

        if ($result && mysqli_num_rows($result) > 0) {
            return mysqli_fetch_assoc($result);
        }

        return null;
    }

    // --------------------------------------------------
    // Delete refresh token (logout)
    // --------------------------------------------------
    public static function deleteRefreshToken($token)
    {
        $db    = getDB();
        $token = mysqli_real_escape_string($db, $token);

        $sql = "DELETE FROM refresh_tokens WHERE token = '$token'";
        mysqli_query($db, $sql);
    }
}