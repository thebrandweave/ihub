<?php
// admin/auth/login.php
require_once "../config/config.php";
require_once "jwt_helper.php";
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin' LIMIT 1");
    $stmt->execute([$email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && password_verify($password, $admin['password_hash'])) {
        $accessToken = generateAccessToken($admin);
        $refreshRaw = generateRefreshTokenString();
        $refreshHash = hashToken($refreshRaw);
        $exp = date('Y-m-d H:i:s', time() + REFRESH_TOKEN_EXP_SECONDS);

        $pdo->prepare("INSERT INTO refresh_tokens (user_id, token_hash, user_agent, ip_address, expires_at)
                       VALUES (?, ?, ?, ?, ?)")
            ->execute([$admin['user_id'], $refreshHash, $_SERVER['HTTP_USER_AGENT'] ?? '', $_SERVER['REMOTE_ADDR'] ?? '', $exp]);

        setAuthCookies($accessToken, $refreshRaw, time() + REFRESH_TOKEN_EXP_SECONDS);
        $_SESSION['admin_id'] = $admin['user_id'];

        header("Location: ../index.php");
        exit;
    } else {
        $error = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Admin Login</title>
</head>
<body>
  <h2>Admin Login</h2>
  <?php if (!empty($error)) echo "<p style='color:red'>$error</p>"; ?>
  <form method="POST">
    <input type="email" name="email" placeholder="Email" required><br>
    <input type="password" name="password" placeholder="Password" required><br>
    <button type="submit">Login</button>
  </form>
</body>
</html>
