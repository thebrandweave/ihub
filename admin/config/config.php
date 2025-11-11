<?php
// admin/config/config.php
// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "ihub_electronics";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

// JWT settings
// IMPORTANT: Replace with environment variable or secure secret in production
const JWT_SECRET = 'replace_with_a_very_long_random_secret_string_!@#123';
const ACCESS_TOKEN_EXP_SECONDS = 900;    // 15 minutes
const REFRESH_TOKEN_EXP_SECONDS = 60 * 60 * 24 * 30; // 30 days

// Cookie settings
const COOKIE_PATH = '/';
const COOKIE_SECURE = true;   // set true in production (requires HTTPS)
const COOKIE_HTTPONLY = true;
const COOKIE_SAMESITE = 'Strict'; // or 'Lax' depending on your needs
