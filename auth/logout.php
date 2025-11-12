<?php
require_once __DIR__ . '/../admin/config/config.php';
require_once __DIR__ . '/jwt_helper.php';
session_start();

$refreshRaw = $_COOKIE['refresh_token'] ?? null;
if ($refreshRaw) {
    $stmt = $pdo->prepare("SELECT * FROM refresh_tokens WHERE revoked = 0");
    $stmt->execute();
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        if (verifyRefreshTokenHash($refreshRaw, $row['token_hash'])) {
            $pdo->prepare("UPDATE refresh_tokens SET revoked = 1 WHERE id = ?")->execute([$row['id']]);
            break;
        }
    }
}

clearAuthCookies();
session_unset();
session_destroy();
// Redirect to login page (same directory)
header("Location: login.php?msg=Logged out");
exit;

