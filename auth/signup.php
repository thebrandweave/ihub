<?php
// auth/signup.php
require_once __DIR__ . '/../config/config.php';
session_start();

$message = "";

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'] ?? 'customer';

    // Validation
    if (empty($name) || empty($email) || empty($password)) {
        $message = "<p style='color:red;'>All fields are required.</p>";
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $message = "<p style='color:red;'>Email already exists!</p>";
        } else {
            // Insert new user
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$name, $email, $hashed, $role]);
            $message = "<p style='color:green;'>✅ Account created successfully! You can now <a href='login.php'>login</a>.</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Signup</title>
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

    .signup-box {
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

    input, select {
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
  <div class="signup-box">
    <h2>Sign Up</h2>
    <?= $message ?>
    <form method="POST">
      <input type="text" name="name" placeholder="Full Name" required><br>
      <input type="email" name="email" placeholder="Email Address" required><br>
      <input type="password" name="password" placeholder="Password" required><br>
      
      <select name="role">
        <option value="customer">Customer</option>
        <option value="admin">Admin</option>
      </select><br>

      <button type="submit">Sign Up</button>
    </form>

    <p>Already have an account? <a href="login.php">Login</a></p>
  </div>
</body>
</html>
