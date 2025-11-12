<?php
require_once "../../auth/check_auth.php";
require_once "../config/config.php";

function generateSlug(string $text): string
{
    $slug = strtolower(trim($text));
    $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
}

$categories = $pdo->query("SELECT category_id, name FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$errors = [];

$formData = [
    'name' => '',
    'slug' => '',
    'description' => '',
    'brand' => '',
    'category_id' => '',
    'price' => '',
    'stock' => '',
    'discount' => '',
    'thumbnail' => '',
    'status' => 'active',
    'image_urls' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($formData as $key => $default) {
        if ($key === 'category_id') {
            $formData[$key] = $_POST[$key] ?? '';
        } else {
            $formData[$key] = trim($_POST[$key] ?? '');
        }
    }

    if ($formData['name'] === '') {
        $errors[] = "Product name is required.";
    }

    if ($formData['price'] === '' || !is_numeric($formData['price'])) {
        $errors[] = "A valid price is required.";
    }

    if ($formData['stock'] === '' || !ctype_digit((string) $formData['stock'])) {
        $errors[] = "Stock must be a whole number.";
    }

    if ($formData['discount'] !== '' && !is_numeric($formData['discount'])) {
        $errors[] = "Discount must be a number.";
    }

    $statusOptions = ['active', 'inactive'];
    if (!in_array($formData['status'], $statusOptions, true)) {
        $errors[] = "Invalid status selected.";
    }

    if ($formData['slug'] === '' && $formData['name'] !== '') {
        $formData['slug'] = generateSlug($formData['name']);
    }

    $imageUrls = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $formData['image_urls'])));

    if (!empty($formData['slug'])) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE slug = ?");
        $stmt->execute([$formData['slug']]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Slug already exists. Please provide a unique slug.";
        }
    }

    if (empty($errors)) {
        $price = number_format((float)$formData['price'], 2, '.', '');
        $stock = (int)$formData['stock'];
        $discount = ($formData['discount'] === '') ? null : number_format((float)$formData['discount'], 2, '.', '');
        $categoryId = $formData['category_id'] !== '' ? (int)$formData['category_id'] : null;
        $thumbnail = $formData['thumbnail'];

        if ($thumbnail === '' && !empty($imageUrls)) {
            $thumbnail = $imageUrls[0];
        }

        try {
            $pdo->beginTransaction();

            $insertProduct = $pdo->prepare("
                INSERT INTO products
                    (name, slug, description, brand, category_id, price, stock, discount, thumbnail, status)
                VALUES
                    (:name, :slug, :description, :brand, :category_id, :price, :stock, :discount, :thumbnail, :status)
            ");

            $insertProduct->execute([
                ':name' => $formData['name'],
                ':slug' => $formData['slug'] !== '' ? $formData['slug'] : null,
                ':description' => $formData['description'] !== '' ? $formData['description'] : null,
                ':brand' => $formData['brand'] !== '' ? $formData['brand'] : null,
                ':category_id' => $categoryId,
                ':price' => $price,
                ':stock' => $stock,
                ':discount' => $discount,
                ':thumbnail' => $thumbnail !== '' ? $thumbnail : null,
                ':status' => $formData['status']
            ]);

            $productId = (int)$pdo->lastInsertId();

            if (!empty($imageUrls)) {
                $insertImage = $pdo->prepare("
                    INSERT INTO product_images (product_id, image_url, is_primary)
                    VALUES (:product_id, :image_url, :is_primary)
                ");

                foreach ($imageUrls as $index => $url) {
                    $insertImage->execute([
                        ':product_id' => $productId,
                        ':image_url' => $url,
                        ':is_primary' => $index === 0 ? 1 : 0
                    ]);
                }
            }

            $pdo->commit();

            header("Location: index.php?msg=Product created successfully");
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = "Failed to save product: " . $e->getMessage();
        }
    }
}

include "../includes/header.php";
?>

<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-semibold text-gray-700">Add Product</h2>
    <a href="index.php" class="text-gray-500 hover:text-gray-700">Back to products</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="mb-4 px-4 py-3 bg-red-100 text-red-800 rounded border border-red-200">
        <ul class="list-disc pl-5 space-y-1">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700">Product Name *</label>
            <input type="text" name="name" id="name" value="<?= htmlspecialchars($formData['name'], ENT_QUOTES, 'UTF-8') ?>" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
        </div>
        <div>
            <label for="slug" class="block text-sm font-medium text-gray-700">Slug</label>
            <input type="text" name="slug" id="slug" value="<?= htmlspecialchars($formData['slug'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Auto-generated if left blank" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div>
            <label for="brand" class="block text-sm font-medium text-gray-700">Brand</label>
            <input type="text" name="brand" id="brand" value="<?= htmlspecialchars($formData['brand'], ENT_QUOTES, 'UTF-8') ?>" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div>
            <label for="category_id" class="block text-sm font-medium text-gray-700">Category</label>
            <select name="category_id" id="category_id" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                <option value="">-- Select category --</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= (int)$category['category_id'] ?>" <?= ($formData['category_id'] !== '' && (int)$formData['category_id'] === (int)$category['category_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="price" class="block text-sm font-medium text-gray-700">Price (₱) *</label>
            <input type="number" name="price" id="price" step="0.01" min="0" value="<?= htmlspecialchars($formData['price'], ENT_QUOTES, 'UTF-8') ?>" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
        </div>
        <div>
            <label for="stock" class="block text-sm font-medium text-gray-700">Stock *</label>
            <input type="number" name="stock" id="stock" min="0" value="<?= htmlspecialchars($formData['stock'], ENT_QUOTES, 'UTF-8') ?>" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
        </div>
        <div>
            <label for="discount" class="block text-sm font-medium text-gray-700">Discount (%)</label>
            <input type="number" name="discount" id="discount" step="0.01" min="0" max="100" value="<?= htmlspecialchars($formData['discount'], ENT_QUOTES, 'UTF-8') ?>" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div>
            <label for="status" class="block text-sm font-medium text-gray-700">Status *</label>
            <select name="status" id="status" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                <option value="active" <?= $formData['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $formData['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>
        <div class="md:col-span-2">
            <label for="thumbnail" class="block text-sm font-medium text-gray-700">Thumbnail URL</label>
            <input type="url" name="thumbnail" id="thumbnail" value="<?= htmlspecialchars($formData['thumbnail'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Optional. Defaults to first image URL." class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div class="md:col-span-2">
            <label for="image_urls" class="block text-sm font-medium text-gray-700">Image URLs</label>
            <textarea name="image_urls" id="image_urls" rows="5" placeholder="Enter one image URL per line. The first image becomes the primary image." class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"><?= htmlspecialchars($formData['image_urls'], ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
        <div class="md:col-span-2">
            <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
            <textarea name="description" id="description" rows="6" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"><?= htmlspecialchars($formData['description'], ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
    </div>
    <div class="flex items-center space-x-4">
        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Save Product</button>
        <a href="index.php" class="text-gray-500 hover:text-gray-700">Cancel</a>
    </div>
</form>

<?php include "../includes/footer.php"; ?>

