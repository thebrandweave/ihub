<?php
require_once "../../auth/check_auth.php";
require_once "../../config/config.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$name = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$parent_id = isset($_POST['parent_id']) && $_POST['parent_id'] !== '' ? (int)$_POST['parent_id'] : null;

$errors = [];

if ($name === '') {
    $errors[] = "Category name is required.";
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

try {
    // Check if category name already exists
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE name = ?");
    $checkStmt->execute([$name]);
    if ($checkStmt->fetchColumn() > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'errors' => ['Category name already exists.']]);
        exit;
    }

    // Validate parent_id if provided
    if ($parent_id !== null) {
        $parentStmt = $pdo->prepare("SELECT category_id FROM categories WHERE category_id = ?");
        $parentStmt->execute([$parent_id]);
        if ($parentStmt->fetchColumn() === 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'errors' => ['Invalid parent category selected.']]);
            exit;
        }
    }

    // Insert new category
    $insertStmt = $pdo->prepare("
        INSERT INTO categories (name, description, parent_id)
        VALUES (:name, :description, :parent_id)
    ");

    $insertStmt->execute([
        ':name' => $name,
        ':description' => $description !== '' ? $description : null,
        ':parent_id' => $parent_id
    ]);

    $categoryId = (int)$pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'category' => [
            'category_id' => $categoryId,
            'name' => $name,
            'description' => $description,
            'parent_id' => $parent_id
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to create category: ' . $e->getMessage()]);
}

