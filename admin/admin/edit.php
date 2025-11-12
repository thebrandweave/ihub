<?php
require_once "../../auth/check_auth.php";
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

<h2 class="text-2xl font-semibold text-gray-700 mb-6">Edit Admin</h2>

<form method="POST" class="space-y-6">
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
        <input type="text" name="name" id="name" value="<?= htmlspecialchars($admin['full_name']) ?>" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
    </div>
    <div>
        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
        <input type="email" name="email" id="email" value="<?= htmlspecialchars($admin['email']) ?>" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
    </div>
    <div>
        <label for="password" class="block text-sm font-medium text-gray-700">New Password</label>
        <input type="password" name="password" id="password" placeholder="Leave blank to keep old password" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
        <p class="mt-2 text-sm text-gray-500">Leave blank to keep the current password.</p>
    </div>
    <div class="flex items-center space-x-4">
        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Update Admin</button>
        <a href="index.php" class="text-gray-500 hover:text-gray-700">Cancel</a>
    </div>
</form>

<?php include "../includes/footer.php"; ?>
