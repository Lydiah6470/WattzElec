<?php
session_start();
include 'includes/db.php';

// Redirect to login if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get product ID and quantity from URL
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
$quantity = isset($_GET['quantity']) ? intval($_GET['quantity']) : 1;

if ($product_id === 0) {
    $_SESSION['error_message'] = 'Invalid product ID.';
    header('Location: products.php');
    exit;
}

// Fetch product details to validate existence
$query = "SELECT product_id, name, price, stock_quantity, 
         price * (1 - COALESCE(discount, 0)/100) as final_price 
         FROM products WHERE product_id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    $_SESSION['error_message'] = 'Product not found.';
    header('Location: products.php');
    exit;
}

// Validate stock availability
$quantity = 1; // Default quantity to add
if ($product['stock_quantity'] <= 0) {
    $_SESSION['error_message'] = 'Sorry, "' . htmlspecialchars($product['name']) . '" is out of stock.';
    header('Location: product_details.php?id=' . $product_id);
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
    // Keep the existing price if it exists, otherwise update it
    if (!isset($_SESSION['cart'][$product_id]['price'])) {
        $_SESSION['cart'][$product_id]['price'] = $product['final_price'];
    }
    $newQty = $_SESSION['cart'][$product_id]['quantity'];
    // Update in DB
    $cartCheck = $conn->prepare("SELECT cart_id FROM cart WHERE user_id = ? AND product_id = ?");
    $cartCheck->execute([$_SESSION['user_id'], $product_id]);
    if ($cartCheck->fetch()) {
        $updateCart = $conn->prepare("UPDATE cart SET quantity = ?, added_at = CURRENT_TIMESTAMP WHERE user_id = ? AND product_id = ?");
        $updateCart->execute([$newQty, $_SESSION['user_id'], $product_id]);
    } else {
        $insertCart = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity, added_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP)");
        $insertCart->execute([$_SESSION['user_id'], $product_id, $newQty]);
    }
    $_SESSION['success_message'] = 'Added ' . $newQty . ' ' . htmlspecialchars($product['name']) . '(s) to your cart.';
} else {
    // Add the product to the cart with default quantity
    $_SESSION['cart'][$product_id] = [
        'quantity' => $quantity,
        'price' => $product['final_price'], // Store the final price (after discount) at the time of adding
    ];
    // Insert into DB
    $cartCheck = $conn->prepare("SELECT cart_id FROM cart WHERE user_id = ? AND product_id = ?");
    $cartCheck->execute([$_SESSION['user_id'], $product_id]);
    if ($cartCheck->fetch()) {
        $updateCart = $conn->prepare("UPDATE cart SET quantity = ?, added_at = CURRENT_TIMESTAMP WHERE user_id = ? AND product_id = ?");
        $updateCart->execute([$quantity, $_SESSION['user_id'], $product_id]);
    } else {
        $insertCart = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity, added_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP)");
        $insertCart->execute([$_SESSION['user_id'], $product_id, $quantity]);
    }
    $_SESSION['success_message'] = 'Added ' . htmlspecialchars($product['name']) . ' to your cart.';
}

// Redirect back to the product details page or cart page
header("Location: product_details.php?id=$product_id");
exit;
?>