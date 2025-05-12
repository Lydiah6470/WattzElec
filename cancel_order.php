<?php
session_start();
require_once 'includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Check if order_id is provided
if (!isset($_POST['order_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Order ID is required']);
    exit;
}

$user_id = $_SESSION['user_id'];
$order_id = intval($_POST['order_id']);

try {
    // Start transaction
    $conn->beginTransaction();

    // Check if order exists and belongs to user
    $stmt = $conn->prepare("SELECT status FROM user_order WHERE order_id = ? AND user_id = ?");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception('Order not found or does not belong to you');
    }

    // Check if order can be cancelled (only pending or processing orders)
    if (!in_array($order['status'], ['pending', 'processing'])) {
        throw new Exception('This order cannot be cancelled');
    }

    // Get order items to restore stock
    $stmt = $conn->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Restore stock for each item
    $updateStock = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE product_id = ?");
    foreach ($items as $item) {
        $updateStock->execute([$item['quantity'], $item['product_id']]);
    }

    // Update order status
    $stmt = $conn->prepare("UPDATE user_order SET status = 'cancelled', payment_status = 'cancelled' WHERE order_id = ?");
    $stmt->execute([$order_id]);

    // Commit transaction
    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Order cancelled successfully']);

} catch (Exception $e) {
    // Rollback on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
