<?php
session_start();
include 'includes/db.php';

// Redirect to login if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get product ID from URL
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

if ($product_id === 0) {
    echo "<p>Invalid product ID.</p>";
    exit;
}

// Fetch product details to validate existence
$query = "SELECT id, price, stock FROM products WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo "<p>Product not found.</p>";
    exit;
}

// Validate stock availability
$quantity = 1; // Default quantity to add
if ($product['stock'] <= 0) {
    echo "<p>This product is out of stock.</p>";
    exit;
}

// Initialize the cart in session if not already set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Check if the product is already in the cart
if (isset($_SESSION['cart'][$product_id])) {
    // Increment the quantity if the product is already in the cart
    $_SESSION['cart'][$product_id]['quantity'] += $quantity;
} else {
    // Add the product to the cart with default quantity
    $_SESSION['cart'][$product_id] = [
        'quantity' => $quantity,
        'price' => $product['price'], // Store the price at the time of adding
    ];
}

// Redirect back to the product details page or cart page
header("Location: product_details.php?id=$product_id");
exit;
?>