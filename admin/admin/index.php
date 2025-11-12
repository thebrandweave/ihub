<?php
require_once "../../auth/check_auth.php";
require_once "../config/config.php";
include "../includes/header.php";

$stmt = $pdo->query("SELECT * FROM users WHERE role = 'admin'");
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Admin Accounts</h2>
<a href="add.php" class="btn">Add New Admin</a>

<table border="1" cellpadding="10">
  <tr>
    <th>ID</th>
    <th>Name</th>
    <th>Email</th>
    <th>Actions</th>
  </tr>
  <?php foreach ($admins as $a): ?>
  <tr>
    <td><?= $a['user_id'] ?></td>
    <td><?= htmlspecialchars($a['full_name']) ?></td>
    <td><?= htmlspecialchars($a['email']) ?></td>
    <td>
      <a href="edit.php?id=<?= $a['user_id'] ?>">Edit</a> |
      <a href="delete.php?id=<?= $a['user_id'] ?>" onclick="return confirm('Delete this admin?')">Delete</a>
    </td>
  </tr>
  <?php endforeach; ?>
</table>

<?php include "../includes/footer.php"; ?>
