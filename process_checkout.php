<?php
session_start();
include 'includes/db.php';

// Redirect to login if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$cart = $_SESSION['cart'] ?? [];

if (empty($cart)) {
    echo "<p>Your cart is empty. <a href='products.php'>Shop now</a>.</p>";
    exit;
}

// Collect and validate form data
$full_name = trim($_POST['full_name']);
$address = trim($_POST['address']);
$city = trim($_POST['city']);
$phone = trim($_POST['phone']);
$payment_method = trim($_POST['payment_method']);

if (empty($full_name) || empty($address) || empty($city) || empty($phone) || empty($payment_method)) {
    echo "<p class='text-danger'>All fields are required.</p>";
    exit;
}

// Calculate total amount
$product_ids = array_keys($cart);
$placeholders = implode(',', array_fill(0, count($product_ids), '?'));
$query = "SELECT id, price, discount FROM products WHERE id IN ($placeholders)";
$stmt = $conn->prepare($query);
$stmt->execute($product_ids);

$total = 0;
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $product) {
    $originalPrice = $product['price'];
    $discountPercentage = $product['discount'] ?? 0;
    $discountedPrice = $originalPrice - ($originalPrice * ($discountPercentage / 100));
    $quantity = intval($cart[$product['id']]['quantity']);
    $total += $discountedPrice * $quantity;
}

try {
    $conn->beginTransaction();

    // Insert shipping details into the shipping_details table
    $shippingQuery = "INSERT INTO shipping_detail (full_name, address, city, phone) 
                      VALUES (?, ?, ?, ?)";
    $shippingStmt = $conn->prepare($shippingQuery);
    $shippingStmt->execute([$full_name, $address, $city, $phone]);
    $shipping_id = $conn->lastInsertId(); // Get the ID of the newly created shipping record

    // Insert the order into the orders table
    $orderQuery = "INSERT INTO orders (user_id, total_amount, payment_method, status, shipping_id, created_at) 
                   VALUES (?, ?, ?, 'pending', ?, NOW())";
    $orderStmt = $conn->prepare($orderQuery);
    $orderStmt->execute([$user_id, $total, $payment_method, $shipping_id]);
    $order_id = $conn->lastInsertId(); // Get the ID of the newly created order

    // Insert order items into the order_items table
    $itemQuery = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
    $itemStmt = $conn->prepare($itemQuery);

    foreach ($cart as $product_id => $item) {
        $product = $products[$product_id] ?? null;
        if (!$product) continue;

        $originalPrice = $product['price'];
        $discountPercentage = $product['discount'] ?? 0;
        $discountedPrice = $originalPrice - ($originalPrice * ($discountPercentage / 100));
        $quantity = intval($item['quantity']);

        $itemStmt->execute([$order_id, $product_id, $quantity, $discountedPrice]);
    }

    // Commit the transaction
    $conn->commit();

    // Clear the cart
    unset($_SESSION['cart']);

    // Redirect to paybill.php with the order ID
    header("Location: paybill.php?order_id=$order_id");
    exit;
} catch (Exception $e) {
    $conn->rollBack();
    echo "<p class='text-danger'>An error occurred while processing your order: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}
?>