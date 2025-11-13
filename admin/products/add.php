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

function validateImageFile($file): array
{
    $errors = [];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
            $errors[] = "File size exceeds maximum allowed size (5MB).";
        } elseif ($file['error'] === UPLOAD_ERR_NO_FILE) {
            // No file uploaded is okay (optional field)
            return ['valid' => false, 'errors' => []];
        } else {
            $errors[] = "File upload error: " . $file['error'];
        }
        return ['valid' => false, 'errors' => $errors];
    }
    
    // Check file size (5MB max)
    $maxSize = 5 * 1024 * 1024; // 5MB in bytes
    if ($file['size'] > $maxSize) {
        $errors[] = "File size exceeds maximum allowed size (5MB).";
        return ['valid' => false, 'errors' => $errors];
    }
    
    // Check file type
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes, true)) {
        $errors[] = "Invalid file type. Only JPEG, PNG, GIF, and WebP images are allowed.";
        return ['valid' => false, 'errors' => $errors];
    }
    
    return ['valid' => true, 'errors' => []];
}

function uploadImageFile($file, $uploadDir): ?string
{
    $validation = validateImageFile($file);
    if (!$validation['valid']) {
        return null;
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('product_', true) . '_' . time() . '.' . $extension;
    $targetPath = $uploadDir . '/' . $filename;
    
    // Ensure upload directory exists
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        // Return only filename (not full path) to store in database
        return $filename;
    }
    
    return null;
}

$categories = $pdo->query("SELECT category_id, name FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$errors = [];
$uploadDir = __DIR__ . '/../../uploads/products';

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
    'status' => 'active'
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

    if (!empty($formData['slug'])) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE slug = ?");
        $stmt->execute([$formData['slug']]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Slug already exists. Please provide a unique slug.";
        }
    }

    // Handle thumbnail upload
    $thumbnailPath = null;
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] !== UPLOAD_ERR_NO_FILE) {
        $validation = validateImageFile($_FILES['thumbnail']);
        if (!$validation['valid']) {
            $errors = array_merge($errors, $validation['errors']);
        } else {
            $thumbnailPath = uploadImageFile($_FILES['thumbnail'], $uploadDir);
            if ($thumbnailPath === null) {
                $errors[] = "Failed to upload thumbnail image.";
            }
        }
    }

    // Handle product images upload
    $uploadedImages = [];
    if (isset($_FILES['product_images']) && is_array($_FILES['product_images']['name'])) {
        $imageCount = count($_FILES['product_images']['name']);
        for ($i = 0; $i < $imageCount; $i++) {
            if ($_FILES['product_images']['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            
            $file = [
                'name' => $_FILES['product_images']['name'][$i],
                'type' => $_FILES['product_images']['type'][$i],
                'tmp_name' => $_FILES['product_images']['tmp_name'][$i],
                'error' => $_FILES['product_images']['error'][$i],
                'size' => $_FILES['product_images']['size'][$i]
            ];
            
            $validation = validateImageFile($file);
            if (!$validation['valid']) {
                $errors = array_merge($errors, $validation['errors']);
            } else {
                $imagePath = uploadImageFile($file, $uploadDir);
                if ($imagePath !== null) {
                    $uploadedImages[] = $imagePath;
                } else {
                    $errors[] = "Failed to upload image: " . $file['name'];
                }
            }
        }
    }

    // Set thumbnail to first uploaded image if not provided
    if ($thumbnailPath === null && !empty($uploadedImages)) {
        $thumbnailPath = $uploadedImages[0];
    }

    if (empty($errors)) {
        $price = number_format((float)$formData['price'], 2, '.', '');
        $stock = (int)$formData['stock'];
        $discount = ($formData['discount'] === '') ? null : number_format((float)$formData['discount'], 2, '.', '');
        $categoryId = $formData['category_id'] !== '' ? (int)$formData['category_id'] : null;

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
                ':thumbnail' => $thumbnailPath,
                ':status' => $formData['status']
            ]);

            $productId = (int)$pdo->lastInsertId();

            if (!empty($uploadedImages)) {
                $insertImage = $pdo->prepare("
                    INSERT INTO product_images (product_id, image_url, is_primary)
                    VALUES (:product_id, :image_url, :is_primary)
                ");

                foreach ($uploadedImages as $index => $imagePath) {
                    $insertImage->execute([
                        ':product_id' => $productId,
                        ':image_url' => $imagePath,
                        ':is_primary' => $index === 0 ? 1 : 0
                    ]);
                }
            }

            $pdo->commit();

            header("Location: index.php?msg=Product created successfully");
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            // Clean up uploaded files on error
            if ($thumbnailPath && file_exists($uploadDir . '/' . $thumbnailPath)) {
                @unlink($uploadDir . '/' . $thumbnailPath);
            }
            foreach ($uploadedImages as $imagePath) {
                if (file_exists($uploadDir . '/' . $imagePath)) {
                    @unlink($uploadDir . '/' . $imagePath);
                }
            }
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

<form method="POST" enctype="multipart/form-data" class="space-y-6">
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
            <div class="flex gap-2">
                <select name="category_id" id="category_id" class="mt-1 flex-1 px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <option value="">-- Select category --</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int)$category['category_id'] ?>" <?= ($formData['category_id'] !== '' && (int)$formData['category_id'] === (int)$category['category_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" id="createCategoryBtn" class="mt-1 px-4 py-2 bg-green-500 hover:bg-green-700 text-white font-bold rounded text-sm whitespace-nowrap">
                    + New
                </button>
            </div>
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
            <label for="thumbnail" class="block text-sm font-medium text-gray-700">Thumbnail Image</label>
            <input type="file" name="thumbnail" id="thumbnail" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            <p class="mt-1 text-sm text-gray-500">Optional. Upload a thumbnail image (JPEG, PNG, GIF, or WebP, max 5MB). Defaults to first product image if not provided.</p>
        </div>
        <div class="md:col-span-2">
            <label for="product_images" class="block text-sm font-medium text-gray-700">Product Images</label>
            <input type="file" name="product_images[]" id="product_images" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" multiple class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            <p class="mt-1 text-sm text-gray-500">Select multiple images (JPEG, PNG, GIF, or WebP, max 5MB each). The first image becomes the primary image.</p>
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

<!-- Create Category Modal -->
<div id="categoryModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Create New Category</h3>
                <button type="button" id="closeCategoryModal" class="text-gray-400 hover:text-gray-600">
                    <span class="text-2xl">&times;</span>
                </button>
            </div>
            <form id="createCategoryForm" class="space-y-4">
                <div>
                    <label for="new_category_name" class="block text-sm font-medium text-gray-700">Category Name *</label>
                    <input type="text" name="name" id="new_category_name" required class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label for="new_category_description" class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea name="description" id="new_category_description" rows="3" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"></textarea>
                </div>
                <div>
                    <label for="new_category_parent" class="block text-sm font-medium text-gray-700">Parent Category (Optional)</label>
                    <select name="parent_id" id="new_category_parent" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- None (Top Level) --</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= (int)$category['category_id'] ?>">
                                <?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div id="categoryModalErrors" class="hidden text-red-600 text-sm"></div>
                <div class="flex items-center justify-end space-x-3 pt-4">
                    <button type="button" id="cancelCategoryBtn" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold rounded">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-500 hover:bg-blue-700 text-white font-bold rounded">Create Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('categoryModal');
    const createBtn = document.getElementById('createCategoryBtn');
    const closeBtn = document.getElementById('closeCategoryModal');
    const cancelBtn = document.getElementById('cancelCategoryBtn');
    const form = document.getElementById('createCategoryForm');
    const categorySelect = document.getElementById('category_id');
    const errorDiv = document.getElementById('categoryModalErrors');

    function openModal() {
        modal.classList.remove('hidden');
    }

    function closeModal() {
        modal.classList.add('hidden');
        form.reset();
        errorDiv.classList.add('hidden');
        errorDiv.textContent = '';
    }

    createBtn.addEventListener('click', openModal);
    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);

    // Close modal when clicking outside
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeModal();
        }
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Creating...';
        errorDiv.classList.add('hidden');
        errorDiv.textContent = '';

        fetch('create_category.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Add new category to dropdown
                const option = document.createElement('option');
                option.value = data.category.category_id;
                option.textContent = data.category.name;
                option.selected = true;
                categorySelect.appendChild(option);
                
                // Also add to parent category dropdown in modal
                const parentSelect = document.getElementById('new_category_parent');
                const parentOption = document.createElement('option');
                parentOption.value = data.category.category_id;
                parentOption.textContent = data.category.name;
                parentSelect.appendChild(parentOption);
                
                closeModal();
            } else {
                // Show errors
                let errorText = '';
                if (data.errors && Array.isArray(data.errors)) {
                    errorText = data.errors.join('<br>');
                } else if (data.error) {
                    errorText = data.error;
                } else {
                    errorText = 'Failed to create category.';
                }
                errorDiv.innerHTML = errorText;
                errorDiv.classList.remove('hidden');
            }
        })
        .catch(error => {
            errorDiv.textContent = 'An error occurred. Please try again.';
            errorDiv.classList.remove('hidden');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
    });
});
</script>

<?php include "../includes/footer.php"; ?>

