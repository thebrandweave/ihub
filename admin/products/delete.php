<?php
require_once "../../auth/check_auth.php";
require_once "../config/config.php";

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($productId > 0) {
    try {
        $stmt = $pdo->prepare("DELETE FROM products WHERE product_id = ?");
        $stmt->execute([$productId]);

        if ($stmt->rowCount() > 0) {
            header("Location: index.php?msg=Product deleted successfully");
            exit;
        }

        header("Location: index.php?error=Product not found or already deleted.");
        exit;
    } catch (PDOException $e) {
        header("Location: index.php?error=" . urlencode("Failed to delete product: " . $e->getMessage()));
        exit;
    }
}

header("Location: index.php?error=Invalid product selection.");
exit;

