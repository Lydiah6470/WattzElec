<?php
session_start();
require_once 'includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: my_orders.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

// Validate inputs
if (!$order_id || !$product_id || !$rating || !$comment) {
    $_SESSION['alert'] = ['type' => 'danger', 'message' => 'All fields are required'];
    header("Location: view_order.php?order_id=$order_id");
    exit();
}

// Validate rating range
if ($rating < 1 || $rating > 5) {
    $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Invalid rating value'];
    header("Location: view_order.php?order_id=$order_id");
    exit();
}

try {
    // Check if order belongs to user and is delivered
    $order_check = $conn->prepare("
        SELECT status 
        FROM user_order 
        WHERE order_id = ? AND user_id = ? AND status = 'delivered'
    ");
    $order_check->execute([$order_id, $user_id]);
    
    if (!$order_check->fetch()) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Invalid order or order not delivered'];
        header("Location: view_order.php?order_id=$order_id");
        exit();
    }

    // Check if user has already reviewed this product for this order
    $review_check = $conn->prepare("
        SELECT review_id 
        FROM reviews 
        WHERE order_id = ? AND product_id = ? AND user_id = ?
    ");
    $review_check->execute([$order_id, $product_id, $user_id]);
    
    if ($review_check->fetch()) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'You have already reviewed this product'];
        header("Location: view_order.php?order_id=$order_id");
        exit();
    }

    // Insert the review
    $stmt = $conn->prepare("
        INSERT INTO reviews (order_id, product_id, user_id, rating, comment) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$order_id, $product_id, $user_id, $rating, $comment]);

    $_SESSION['alert'] = ['type' => 'success', 'message' => 'Thank you for your review!'];
} catch (PDOException $e) {
    $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Error submitting review: ' . $e->getMessage()];
}

header("Location: view_order.php?order_id=$order_id");
exit();
?>
