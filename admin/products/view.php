<?php
require_once "../../auth/check_auth.php";
require_once "../config/config.php";

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($productId <= 0) {
    header("Location: index.php?error=Invalid product selection.");
    exit;
}

// Fetch product details
$productStmt = $pdo->prepare("
    SELECT 
        p.*,
        c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    WHERE p.product_id = ?
");
$productStmt->execute([$productId]);
$product = $productStmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header("Location: index.php?error=Product not found.");
    exit;
}

// Fetch all product images
$imageStmt = $pdo->prepare("
    SELECT image_url, is_primary 
    FROM product_images 
    WHERE product_id = ? 
    ORDER BY is_primary DESC, image_id ASC
");
$imageStmt->execute([$productId]);
$images = $imageStmt->fetchAll(PDO::FETCH_ASSOC);

// Helper function to get image path
function getImagePath($filename): string
{
    if (!$filename) {
        return '';
    }
    
    // If it's already a full URL or absolute path, return as is
    if (strpos($filename, 'http') === 0 || strpos($filename, '/') === 0) {
        return $filename;
    }
    
    // Construct path relative to project root
    return '../../uploads/products/' . $filename;
}

// Calculate discounted price if discount exists
$originalPrice = (float)$product['price'];
$discount = $product['discount'] ? (float)$product['discount'] : 0;
$discountedPrice = $discount > 0 ? $originalPrice * (1 - ($discount / 100)) : $originalPrice;

include "../includes/header.php";
?>

<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-semibold text-gray-700">View Product</h2>
    <div class="flex gap-2">
        <a href="edit.php?id=<?= (int)$productId ?>" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Edit Product</a>
        <a href="index.php" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">Back to Products</a>
    </div>
</div>

<div class="bg-white rounded shadow-lg overflow-hidden">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 p-6">
        <!-- Product Images Section -->
        <div>
            <h3 class="text-lg font-semibold text-gray-700 mb-4">Product Images</h3>
            <?php if (!empty($images)): ?>
                <div class="space-y-4">
                    <!-- Main/Thumbnail Image -->
                    <?php 
                    $thumbnailSrc = $product['thumbnail'] ?? ($images[0]['image_url'] ?? '');
                    $thumbnailPath = $thumbnailSrc ? getImagePath($thumbnailSrc) : '';
                    ?>
                    <?php if ($thumbnailPath): ?>
                        <div>
                            <p class="text-sm text-gray-600 mb-2">Thumbnail:</p>
                            <img src="<?= htmlspecialchars($thumbnailPath, ENT_QUOTES, 'UTF-8') ?>" 
                                 alt="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>" 
                                 class="w-full h-96 object-contain rounded-lg border border-gray-300 bg-gray-50">
                        </div>
                    <?php endif; ?>
                    
                    <!-- Gallery Images -->
                    <?php if (count($images) > 0): ?>
                        <div>
                            <p class="text-sm text-gray-600 mb-2">Gallery Images (<?= count($images) ?>):</p>
                            <div class="grid grid-cols-2 gap-4">
                                <?php foreach ($images as $img): ?>
                                    <div class="relative">
                                        <?php if ($img['is_primary']): ?>
                                            <span class="absolute top-2 left-2 bg-blue-500 text-white text-xs px-2 py-1 rounded">Primary</span>
                                        <?php endif; ?>
                                        <img src="<?= htmlspecialchars(getImagePath($img['image_url']), ENT_QUOTES, 'UTF-8') ?>" 
                                             alt="Product image" 
                                             class="w-full h-48 object-cover rounded-lg border border-gray-300">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="flex items-center justify-center h-64 bg-gray-100 rounded-lg border border-gray-300">
                    <p class="text-gray-500">No images available</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Product Details Section -->
        <div>
            <h3 class="text-lg font-semibold text-gray-700 mb-4">Product Details</h3>
            <div class="space-y-4">
                <!-- Product Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Product Name</label>
                    <p class="text-xl font-semibold text-gray-900"><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>

                <!-- Brand -->
                <?php if (!empty($product['brand'])): ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Brand</label>
                        <p class="text-lg text-gray-900"><?= htmlspecialchars($product['brand'], ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                <?php endif; ?>

                <!-- Category -->
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Category</label>
                    <p class="text-lg text-gray-900"><?= htmlspecialchars($product['category_name'] ?? 'Unassigned', ENT_QUOTES, 'UTF-8') ?></p>
                </div>

                <!-- Slug -->
                <?php if (!empty($product['slug'])): ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Slug</label>
                        <p class="text-sm text-gray-600 font-mono"><?= htmlspecialchars($product['slug'], ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                <?php endif; ?>

                <!-- Price and Discount -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Price</label>
                        <p class="text-2xl font-bold text-gray-900">₱<?= number_format($originalPrice, 2) ?></p>
                    </div>
                    <?php if ($discount > 0): ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Discount</label>
                            <div>
                                <p class="text-xl font-bold text-green-600"><?= number_format($discount, 2) ?>%</p>
                                <p class="text-lg text-gray-600 line-through">₱<?= number_format($originalPrice, 2) ?></p>
                                <p class="text-xl font-bold text-gray-900">₱<?= number_format($discountedPrice, 2) ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Stock -->
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Stock</label>
                    <p class="text-xl font-semibold <?= (int)$product['stock'] < 5 ? 'text-red-600' : 'text-gray-900' ?>">
                        <?= (int)$product['stock'] ?> units
                        <?php if ((int)$product['stock'] < 5): ?>
                            <span class="text-sm text-red-600">(Low Stock)</span>
                        <?php endif; ?>
                    </p>
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Status</label>
                    <span class="inline-flex px-4 py-2 rounded-full text-sm font-semibold <?= $product['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                        <?= htmlspecialchars(ucfirst($product['status']), ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </div>

                <!-- Description -->
                <?php if (!empty($product['description'])): ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Description</label>
                        <div class="text-gray-700 whitespace-pre-wrap bg-gray-50 p-4 rounded-lg border border-gray-200">
                            <?= nl2br(htmlspecialchars($product['description'], ENT_QUOTES, 'UTF-8')) ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Timestamps -->
                <div class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-200">
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Created At</label>
                        <p class="text-sm text-gray-600">
                            <?= $product['created_at'] ? date('M d, Y h:i A', strtotime($product['created_at'])) : 'N/A' ?>
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Last Updated</label>
                        <p class="text-sm text-gray-600">
                            <?= $product['updated_at'] ? date('M d, Y h:i A', strtotime($product['updated_at'])) : 'N/A' ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-between items-center">
        <div class="flex gap-2">
            <a href="edit.php?id=<?= (int)$productId ?>" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Edit Product
            </a>
            <a href="delete.php?id=<?= (int)$productId ?>" 
               class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded"
               onclick="return confirm('Are you sure you want to delete this product?');">
                Delete Product
            </a>
        </div>
        <a href="index.php" class="text-gray-600 hover:text-gray-800 font-semibold">
            ← Back to Products
        </a>
    </div>
</div>

<?php include "../includes/footer.php"; ?>

