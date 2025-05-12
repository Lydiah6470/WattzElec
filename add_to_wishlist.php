<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = 'Please login to add items to your wishlist.';
    header("Location: login.php");
    exit;
}

$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

if ($product_id === 0) {
    $_SESSION['error_message'] = 'Invalid product ID.';
    header('Location: products.php');
    exit;
}

// Fetch product details to validate existence
$stmt = $conn->prepare("SELECT product_id, name FROM products WHERE product_id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    $_SESSION['error_message'] = 'Product not found.';
    header('Location: products.php');
    exit;
}

// Add to wishlist table in DB if not already there
$wishlistCheck = $conn->prepare("SELECT wishlist_id FROM wishlist WHERE user_id = ? AND product_id = ?");
$wishlistCheck->execute([$_SESSION['user_id'], $product_id]);

if (!$wishlistCheck->fetch()) {
    try {
        $insertWishlist = $conn->prepare("INSERT INTO wishlist (user_id, product_id, added_at) VALUES (?, ?, CURRENT_TIMESTAMP)");
        $insertWishlist->execute([$_SESSION['user_id'], $product_id]);
        $_SESSION['success_message'] = '"' . htmlspecialchars($product['name']) . '" has been added to your wishlist!';
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Sorry, there was an error adding the item to your wishlist. Please try again.';
    }
} else {
    $_SESSION['info_message'] = '"' . htmlspecialchars($product['name']) . '" is already in your wishlist.';
}
header("Location: product_details.php?id=" . $product_id);
exit;
?>