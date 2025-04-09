<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

if ($product_id === 0) {
    echo "<p>Invalid product ID.</p>";
    exit;
}

// Fetch product details to validate existence
$stmt = $conn->prepare("SELECT id FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    echo "<p>Product not found.</p>";
    exit;
}

// Initialize wishlist in session if not already set
if (!isset($_SESSION['wishlist'])) {
    $_SESSION['wishlist'] = [];
}

// Check if the product is already in the wishlist
if (in_array($product_id, $_SESSION['wishlist'])) {
    header("Location: product_details.php?id=" . $product_id);
    exit;
}

// Add product to wishlist
$_SESSION['wishlist'][] = $product_id;

header("Location: product_details.php?id=" . $product_id);
exit;
?>