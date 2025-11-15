<?php
// auth/customer_logout.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/jwt_helper.php';
session_start();

// Revoke refresh token if exists
$refreshRaw = $_COOKIE['refresh_token'] ?? null;
if ($refreshRaw) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM refresh_tokens WHERE revoked = 0");
        $stmt->execute();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (verifyRefreshTokenHash($refreshRaw, $row['token_hash'])) {
                $pdo->prepare("UPDATE refresh_tokens SET revoked = 1 WHERE id = ?")->execute([$row['id']]);
                break;
            }
        }
    } catch (PDOException $e) {
        // Continue with logout even if token revocation fails
    }
}

clearAuthCookies();
session_unset();
session_destroy();

header("Location: ../index.php?msg=Logged out successfully");
exit;

