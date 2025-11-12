<?php
require_once "../../auth/check_auth.php";
require_once "../config/config.php";

$message = $_GET['msg'] ?? null;
$error = $_GET['error'] ?? null;

$stmt = $pdo->query("
    SELECT
        p.product_id,
        p.name,
        p.slug,
        p.brand,
        p.price,
        p.stock,
        p.discount,
        p.status,
        p.created_at,
        p.updated_at,
        COALESCE(c.name, 'Unassigned') AS category_name,
        (
            SELECT image_url
            FROM product_images
            WHERE product_id = p.product_id AND is_primary = 1
            ORDER BY image_id ASC
            LIMIT 1
        ) AS primary_image,
        (
            SELECT COUNT(*)
            FROM product_images
            WHERE product_id = p.product_id
        ) AS image_count
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    ORDER BY p.created_at DESC
");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

include "../includes/header.php";
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h2 class="text-2xl font-semibold text-gray-700">Products</h2>
        <p class="text-sm text-gray-500">Manage catalog items, categories, and gallery images.</p>
    </div>
    <a href="add.php" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Add Product</a>
</div>

<?php if ($message): ?>
    <div class="mb-4 px-4 py-3 bg-green-100 text-green-800 rounded border border-green-200">
        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="mb-4 px-4 py-3 bg-red-100 text-red-800 rounded border border-red-200">
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if (empty($products)): ?>
    <div class="p-6 text-center bg-white rounded shadow">
        <p class="text-gray-600">No products found. Start by adding your first product.</p>
    </div>
<?php else: ?>
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white rounded shadow">
            <thead class="bg-gray-800 text-white">
                <tr>
                    <th class="text-left py-3 px-4 uppercase font-semibold text-sm">Product</th>
                    <th class="text-left py-3 px-4 uppercase font-semibold text-sm">Category</th>
                    <th class="text-left py-3 px-4 uppercase font-semibold text-sm">Price</th>
                    <th class="text-left py-3 px-4 uppercase font-semibold text-sm">Stock</th>
                    <th class="text-left py-3 px-4 uppercase font-semibold text-sm">Images</th>
                    <th class="text-left py-3 px-4 uppercase font-semibold text-sm">Status</th>
                    <th class="text-left py-3 px-4 uppercase font-semibold text-sm">Actions</th>
                </tr>
            </thead>
            <tbody class="text-gray-700">
                <?php foreach ($products as $product): ?>
                    <tr class="border-b border-gray-200 hover:bg-gray-50">
                        <td class="py-3 px-4">
                            <div class="flex items-center space-x-3">
                                <?php if (!empty($product['primary_image'])): ?>
                                    <img src="<?= htmlspecialchars($product['primary_image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>" class="w-12 h-12 object-cover rounded">
                                <?php else: ?>
                                    <div class="w-12 h-12 flex items-center justify-center bg-gray-200 text-gray-500 rounded">N/A</div>
                                <?php endif; ?>
                                <div>
                                    <p class="font-semibold"><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></p>
                                    <?php if (!empty($product['brand'])): ?>
                                        <p class="text-xs text-gray-500"><?= htmlspecialchars($product['brand'], ENT_QUOTES, 'UTF-8') ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($product['slug'])): ?>
                                        <p class="text-xs text-gray-400">Slug: <?= htmlspecialchars($product['slug'], ENT_QUOTES, 'UTF-8') ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="py-3 px-4"><?= htmlspecialchars($product['category_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="py-3 px-4">₱<?= number_format((float)$product['price'], 2) ?></td>
                        <td class="py-3 px-4">
                            <span class="<?= $product['stock'] < 5 ? 'text-red-600 font-semibold' : '' ?>">
                                <?= (int)$product['stock'] ?>
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            <?php if ($product['image_count']): ?>
                                <?= (int)$product['image_count'] ?> image<?= $product['image_count'] == 1 ? '' : 's' ?>
                            <?php else: ?>
                                <span class="text-gray-400">None</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-4">
                            <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold <?= $product['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                <?= htmlspecialchars(ucfirst($product['status']), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            <a href="edit.php?id=<?= (int)$product['product_id'] ?>" class="text-blue-500 hover:text-blue-700 font-semibold mr-4">Edit</a>
                            <a href="delete.php?id=<?= (int)$product['product_id'] ?>" class="text-red-500 hover:text-red-700 font-semibold" onclick="return confirm('Are you sure you want to delete this product?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php include "../includes/footer.php"; ?>

