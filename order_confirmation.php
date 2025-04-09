<?php
session_start();
include 'includes/db.php';
include 'includes/header.php';

// Redirect to login if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get the order ID from the query string
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($order_id === 0) {
    echo "<p>Invalid order ID.</p>";
    include 'includes/footer.php';
    exit;
}

// Fetch order details from the database
$query = "
    SELECT o.id AS order_id, o.total_amount, o.status, o.payment_method, o.created_at,
           s.full_name, s.address, s.city, s.phone
    FROM orders o
    JOIN shipping_details s ON o.shipping_id = s.id
    WHERE o.id = ?
";
$stmt = $conn->prepare($query);
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo "<p>Order not found.</p>";
    include 'includes/footer.php';
    exit;
}

// Fetch order items for the given order ID
$itemsQuery = "
    SELECT oi.product_id, p.name AS product_name, p.image_url, oi.quantity, oi.price
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
";
$itemsStmt = $conn->prepare($itemsQuery);
$itemsStmt->execute([$order_id]);
$orderItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate the total quantity of items
$totalQuantity = array_sum(array_column($orderItems, 'quantity'));
?>

<div class="container mt-5">
    <h2 class="text-center">Order Confirmation</h2>
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">Order Details</h4>
            <p><strong>Order ID:</strong> <?php echo htmlspecialchars($order['order_id']); ?></p>
            <p><strong>Status:</strong> <?php echo htmlspecialchars($order['status']); ?></p>
            <p><strong>Date:</strong> <?php echo date('M j, Y H:i', strtotime($order['created_at'])); ?></p>
            <p><strong>Total Amount:</strong> KSH <?php echo number_format($order['total_amount'], 2); ?></p>
            <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
        </div>
        <div class="card-body">
            <h4 class="card-title">Shipping Details</h4>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($order['full_name']); ?></p>
            <p><strong>Address:</strong> <?php echo htmlspecialchars($order['address']); ?></p>
            <p><strong>City:</strong> <?php echo htmlspecialchars($order['city']); ?></p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
        </div>
        <div class="card-body">
            <h4 class="card-title">Order Summary</h4>
            <p><strong>Total Items:</strong> <?php echo $totalQuantity; ?></p>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Image</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orderItems as $item): 
                        $subtotal = $item['quantity'] * $item['price'];
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                            <td><img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" width="50"></td>
                            <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                            <td>KSH <?php echo number_format($item['price'], 2); ?></td>
                            <td>KSH <?php echo number_format($subtotal, 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="4" class="text-end">Total:</th>
                        <th>KSH <?php echo number_format($order['total_amount'], 2); ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <div class="text-center mt-3">
        <a href="index.php" class="btn btn-primary">Back to Home</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>