<?php
// auth/customer_auth.php - Customer authentication helper
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/jwt_helper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$customer_logged_in = false;
$customer_id = null;
$customer_name = null;
$customer_email = null;

// Check if customer is logged in via session
if (isset($_SESSION['customer_id']) && isset($_SESSION['customer_role']) && $_SESSION['customer_role'] === 'customer') {
    $customer_logged_in = true;
    $customer_id = $_SESSION['customer_id'];
    $customer_name = $_SESSION['customer_name'] ?? null;
    $customer_email = $_SESSION['customer_email'] ?? null;
} else {
    // Check JWT token from cookie
    $accessToken = $_COOKIE['access_token'] ?? null;
    if ($accessToken) {
        $data = decodeAccessToken($accessToken);
        if ($data && isset($data['sub']) && isset($data['role']) && $data['role'] === 'customer') {
            // Fetch customer details
            $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ? AND role = 'customer' LIMIT 1");
            $stmt->execute([$data['sub']]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($customer) {
                $customer_logged_in = true;
                $customer_id = $customer['user_id'];
                $customer_name = $customer['full_name'];
                $customer_email = $customer['email'];
                
                // Set session
                $_SESSION['customer_id'] = $customer_id;
                $_SESSION['customer_name'] = $customer_name;
                $_SESSION['customer_email'] = $customer_email;
                $_SESSION['customer_role'] = 'customer';
            }
        }
    }
}

// Function to get cart count
function getCartCount($pdo, $customer_id) {
    if (!$customer_id) return 0;
    $stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ?");
    $stmt->execute([$customer_id]);
    return (int)($stmt->fetchColumn() ?? 0);
}

// Function to get wishlist count
function getWishlistCount($pdo, $customer_id) {
    if (!$customer_id) return 0;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
    $stmt->execute([$customer_id]);
    return (int)($stmt->fetchColumn() ?? 0);
}

$cart_count = $customer_logged_in ? getCartCount($pdo, $customer_id) : 0;
$wishlist_count = $customer_logged_in ? getWishlistCount($pdo, $customer_id) : 0;

