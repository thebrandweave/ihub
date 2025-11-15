<?php
require_once "../../auth/check_auth.php";
require_once "../../config/config.php";
include "../includes/header.php";

$stmt = $pdo->query("SELECT * FROM users WHERE role = 'admin'");
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-semibold text-gray-700">Admin Accounts</h2>
    <a href="add.php" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Add New Admin</a>
</div>

<div class="overflow-x-auto">
    <table class="min-w-full bg-white">
        <thead class="bg-gray-800 text-white">
            <tr>
                <th class="w-1/3 text-left py-3 px-4 uppercase font-semibold text-sm">Name</th>
                <th class="w-1/3 text-left py-3 px-4 uppercase font-semibold text-sm">Email</th>
                <th class="text-left py-3 px-4 uppercase font-semibold text-sm">Actions</th>
            </tr>
        </thead>
        <tbody class="text-gray-700">
            <?php foreach ($admins as $a): ?>
            <tr class="border-b border-gray-200 hover:bg-gray-100">
                <td class="py-3 px-4"><?= htmlspecialchars($a['full_name']) ?></td>
                <td class="py-3 px-4"><?= htmlspecialchars($a['email']) ?></td>
                <td class="py-3 px-4">
                    <a href="edit.php?id=<?= $a['user_id'] ?>" class="text-blue-500 hover:text-blue-700 font-semibold mr-4">Edit</a>
                    <a href="delete.php?id=<?= $a['user_id'] ?>" class="text-red-500 hover:text-red-700 font-semibold" onclick="return confirm('Are you sure you want to delete this admin?')">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include "../includes/footer.php"; ?>
