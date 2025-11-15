<?php
// auth/login.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/jwt_helper.php';
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

        header("Location: ../admin/index.php");
        exit;
    } else {
        $error = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Login</title>
  <style>
    body {
      font-family: "Segoe UI", sans-serif;
      background: #f7f8fa;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }

    .login-box {
      background: white;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      width: 350px;
      text-align: center;
    }

    h2 {
      margin-bottom: 20px;
      color: #333;
    }

    input {
      width: 90%;
      padding: 10px;
      margin-bottom: 15px;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 14px;
    }

    button {
      background: #007bff;
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 6px;
      cursor: pointer;
      width: 95%;
      font-size: 16px;
      transition: 0.3s;
    }

    button:hover {
      background: #0056b3;
    }

    .error {
      color: red;
      margin-bottom: 15px;
      font-size: 14px;
    }

    a {
      color: #007bff;
      text-decoration: none;
    }

    a:hover {
      text-decoration: underline;
    }

    p {
      font-size: 14px;
      margin-top: 15px;
    }
  </style>
</head>
<body>
  <div class="login-box">
    <h2>Admin Login</h2>
    <?php if (!empty($error)): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
      <input type="email" name="email" placeholder="Email Address" required><br>
      <input type="password" name="password" placeholder="Password" required><br>
      <button type="submit">Login</button>
    </form>
    <p>Don't have an account? <a href="signup.php">Sign Up</a></p>
  </div>
</body>
</html>
