<?php
require_once "../auth/check_auth.php";
require_once "../config/config.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, 'admin')");
    $stmt->execute([$name, $email, $password]);

    header("Location: index.php?msg=Admin added successfully");
    exit;
}

include "../includes/header.php";
?>

<h2>Add New Admin</h2>

<form method="POST">
  <label>Name:</label><br>
  <input type="text" name="name" required><br><br>

  <label>Email:</label><br>
  <input type="email" name="email" required><br><br>

  <label>Password:</label><br>
  <input type="password" name="password" required><br><br>

  <button type="submit">Add Admin</button>
  <a href="index.php">Cancel</a>
</form>

<?php include "../includes/footer.php"; ?>
