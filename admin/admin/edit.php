<?php
require_once "../auth/check_auth.php";
require_once "../config/config.php";

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ? AND role = 'admin'");
$stmt->execute([$id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    die("Admin not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!empty($password)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET full_name=?, email=?, password_hash=? WHERE user_id=?");
        $stmt->execute([$name, $email, $password_hash, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET full_name=?, email=? WHERE user_id=?");
        $stmt->execute([$name, $email, $id]);
    }

    header("Location: index.php?msg=Admin updated successfully");
    exit;
}

include "../includes/header.php";
?>

<h2>Edit Admin</h2>

<form method="POST">
  <label>Name:</label><br>
  <input type="text" name="name" value="<?= htmlspecialchars($admin['full_name']) ?>" required><br><br>

  <label>Email:</label><br>
  <input type="email" name="email" value="<?= htmlspecialchars($admin['email']) ?>" required><br><br>

  <label>New Password (optional):</label><br>
  <input type="password" name="password" placeholder="Leave blank to keep old password"><br><br>

  <button type="submit">Update</button>
  <a href="index.php">Cancel</a>
</form>

<?php include "../includes/footer.php"; ?>
