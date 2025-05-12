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
$recipient_name = trim($_POST['recipient_name']);
$address = trim($_POST['address']);
$city = trim($_POST['city']);
$country = trim($_POST['country']);
$phone = trim($_POST['phone']);
$shipping_method = trim($_POST['shipping_method']);
$payment_method = trim($_POST['payment_method']);

if (empty($recipient_name) || empty($address) || empty($city) || empty($country) || empty($phone) || empty($shipping_method) || empty($payment_method)) {
    echo "<p class='text-danger'>All fields are required.</p>";
    exit;
}

// Calculate shipping cost and estimated delivery date
$shipping_cost = $shipping_method === 'expedited' ? 1500.00 : 500.00;
$estimated_delivery_date = date('Y-m-d', strtotime('+' . ($shipping_method === 'expedited' ? '3' : '7') . ' days'));

// Calculate total amount and validate products exist
$product_ids = array_keys($cart);
$placeholders = implode(',', array_fill(0, count($product_ids), '?'));
$query = "SELECT product_id, stock_quantity, price, discount,
          CASE 
              WHEN discount > 0 THEN price * (1 - discount/100)
              ELSE price 
          END as final_price
          FROM products WHERE product_id IN ($placeholders)";
$stmt = $conn->prepare($query);
$stmt->execute($product_ids);

$valid_products = [];
$stock_quantities = [];
$product_prices = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $valid_products[$row['product_id']] = true;
    $stock_quantities[$row['product_id']] = $row['stock_quantity'];
    $product_prices[$row['product_id']] = $row['final_price'];
}

$total = 0;
foreach ($cart as $product_id => $item) {
    if (!isset($valid_products[$product_id])) {
        throw new Exception('Some products in your cart are no longer available. Please remove them and try again.');
    }
    $quantity = intval($item['quantity']);
    
    // Check if enough stock is available
    if ($quantity > $stock_quantities[$product_id]) {
        throw new Exception('Sorry, some items in your cart are out of stock. Please reduce the quantity or remove them.');
    }
    
    $price = $product_prices[$product_id];
    $total += $price * $quantity;
}

// Add shipping cost to total
$total += $shipping_cost;

try {
    $conn->beginTransaction();

    // Insert shipping details first
    $shippingQuery = "INSERT INTO shipping_detail (recipient_name, address, city, country, phone, shipping_method, shipping_cost, estimated_delivery_date) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $shippingStmt = $conn->prepare($shippingQuery);
    $shippingStmt->execute([
        $recipient_name, 
        $address, 
        $city, 
        $country, 
        $phone, 
        $shipping_method, 
        $shipping_cost, 
        $estimated_delivery_date
    ]);
    $shipping_id = $conn->lastInsertId();
    
    // Insert the order with the total including shipping
    $orderQuery = "INSERT INTO user_order (user_id, shipping_id, total_amount, payment_method, status, payment_status) 
                VALUES (?, ?, ?, ?, 'pending', 'pending')";
    $orderStmt = $conn->prepare($orderQuery);
    $orderStmt->execute([$user_id, $shipping_id, $total, $payment_method]);
    $order_id = $conn->lastInsertId();

    // Insert order items into the order_items table using final_price
    $itemQuery = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
    $itemStmt = $conn->prepare($itemQuery);

    foreach ($cart as $product_id => $item) {
        if (!isset($valid_products[$product_id])) {
            throw new Exception('Some products in your cart are no longer available. Please remove them and try again.');
        }
        $quantity = intval($item['quantity']);
        $price = $product_prices[$product_id];

        $itemStmt->execute([$order_id, $product_id, $quantity, $price]);
        
        // Reduce stock quantity and update status if needed
        $updateStockQuery = "UPDATE products 
            SET stock_quantity = GREATEST(0, stock_quantity - ?),
                status = CASE 
                    WHEN (stock_quantity - ?) <= 0 THEN 'out_of_stock'
                    ELSE 'in_stock'
                END
            WHERE product_id = ?";
        $updateStockStmt = $conn->prepare($updateStockQuery);
        $updateStockStmt->execute([$quantity, $quantity, $product_id]);
    }

    // Commit the transaction
    $conn->commit();

    // Clear the cart from session
    unset($_SESSION['cart']);

    // Clear the cart from database
    $deleteCartQuery = "DELETE FROM cart WHERE user_id = ?";
    $deleteCartStmt = $conn->prepare($deleteCartQuery);
    $deleteCartStmt->execute([$user_id]);

    // Redirect to paybill.php with the order ID
    header("Location: paybill.php?order_id=$order_id");
    exit;
} catch (Exception $e) {
    $conn->rollBack();
    echo "<p class='text-danger'>An error occurred while processing your order: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}
?>