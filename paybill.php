<?php
session_start();
include 'includes/header.php';
include 'includes/db.php';

// Redirect to login if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Handle payment confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_mpesa_payment'])) {
    $order_id = intval($_POST['order_id']);
    $user_id = $_SESSION['user_id'];
    
    // Update order status to pending confirmation
    $update_query = "UPDATE user_order SET payment_status = 'pending_confirmation' WHERE order_id = ? AND user_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->execute([$order_id, $user_id]);
    
    // Redirect to orders page with success message
    $_SESSION['payment_message'] = "Thank you! Your M-Pesa payment is being processed. We will confirm your payment shortly.";
    header("Location: my_orders.php");
    exit;
}

// Check if order_id is provided
if (!isset($_GET['order_id'])) {
    header("Location: my_orders.php");
    exit;
}

$order_id = intval($_GET['order_id']);
$user_id = $_SESSION['user_id'];

// Fetch order details with products and calculate final prices
$query = "SELECT o.*, s.recipient_name, s.address, s.city, s.phone,
          oi.quantity, oi.price as item_price,
          p.name as product_name, p.image_1 as product_image,
          p.price as original_price, p.discount,
          s.shipping_cost,
          CASE 
              WHEN p.discount > 0 THEN p.price * (1 - p.discount/100)
              ELSE p.price 
          END as final_price
          FROM user_order o 
          JOIN shipping_detail s ON o.shipping_id = s.shipping_id 
          JOIN order_items oi ON o.order_id = oi.order_id
          JOIN products p ON oi.product_id = p.product_id
          WHERE o.order_id = ? AND o.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$order_id, $user_id]);
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($order_items)) {
    header("Location: my_orders.php");
    exit;
}

$order = $order_items[0]; // Use first item for order details
$shipping_cost = floatval($order['shipping_cost']); // Get shipping cost from first item

// Calculate total with final prices
$subtotal = 0;
foreach ($order_items as $item) {
    $subtotal += $item['final_price'] * $item['quantity'];
}
$total_amount = $subtotal + $shipping_cost;

// Update the total amount in the database if it's different
if (abs($total_amount - $order['total_amount']) > 0.01) { // Using small epsilon for float comparison
    $update_total = "UPDATE user_order SET total_amount = ? WHERE order_id = ? AND user_id = ?";
    $stmt = $conn->prepare($update_total);
    $stmt->execute([$total_amount, $order_id, $user_id]);
}

if (!$order) {
    header("Location: my_orders.php");
    exit;
}
?>

<div class="container-fluid">
    <h2 class="mt-4">Payment Details</h2>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Order Summary</h4>
                    <p><strong>Order ID:</strong> #<?php echo $order_id; ?></p>
                    
                    <h5 class="mt-3 mb-3">Ordered Items</h5>
                    <?php foreach ($order_items as $item): ?>
                    <div class="d-flex align-items-center mb-3 border-bottom pb-3">
                        <img src="<?php echo htmlspecialchars($item['product_image']); ?>" 
                             alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                             class="img-thumbnail" style="width: 80px; height: 80px; object-fit: cover;">
                        <div class="ms-3">
                            <h6 class="mb-1"><?php echo htmlspecialchars($item['product_name']); ?></h6>
                            <p class="mb-0">Quantity: <?php echo $item['quantity']; ?></p>
                            <?php if ($item['discount'] > 0): ?>
                                <p class="mb-0">
                                    <span class="text-decoration-line-through text-muted">
                                        KSH <?php echo number_format($item['original_price'], 2); ?>
                                    </span>
                                    <span class="text-success">
                                        KSH <?php echo number_format($item['final_price'], 2); ?>
                                    </span>
                                    <span class="badge bg-danger">-<?php echo $item['discount']; ?>%</span>
                                </p>
                            <?php else: ?>
                                <p class="mb-0">Price: KSH <?php echo number_format($item['final_price'], 2); ?></p>
                            <?php endif; ?>
                            <p class="mb-0">Subtotal: KSH <?php echo number_format($item['final_price'] * $item['quantity'], 2); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <div class="mt-3">
                        <p><strong>Subtotal:</strong> KSH <?php echo number_format($subtotal, 2); ?></p>
                        <p><strong>Shipping Cost:</strong> KSH <?php echo number_format($shipping_cost, 2); ?></p>
                        <p><strong>Total Amount:</strong> KSH <?php echo number_format($total_amount, 2); ?></p>
                        <p><strong>Payment Method:</strong> <?php echo ucwords(str_replace('_', ' ', $order['payment_method'])); ?></p>
                    </div>
                    
                    <h5 class="mt-4">Shipping Details</h5>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($order['recipient_name']); ?></p>
                    <p><strong>Address:</strong> <?php echo htmlspecialchars($order['address']); ?></p>
                    <p><strong>City:</strong> <?php echo htmlspecialchars($order['city']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <?php if ($order['payment_method'] === 'mpesa'): ?>
                        <h4 class="card-title">M-Pesa Payment</h4>
                        <p>Please follow these steps to complete your payment:</p>
                        <ol>
                            <li>Go to M-Pesa on your phone</li>
                            <li>Select Pay Bill</li>
                            <li>Enter Business Number: <strong>5632190</strong></li>
                            <li>Enter Account Number: <strong>00<?php echo $order_id; ?></strong></li>
                            <li>Enter Amount: <strong>KSH <?php echo number_format($total_amount, 2); ?></strong></li>
                            <li>Enter your M-Pesa PIN</li>
                            <li>Confirm the transaction</li>
                        </ol>
                        <p class="text-muted mb-4">Once we receive your payment, we will process your order.</p>
                        
                        <!-- M-Pesa Payment Confirmation Button -->
                        <form action="paybill.php" method="POST" class="mt-3">
                            <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                            <button type="submit" name="confirm_mpesa_payment" class="btn btn-success w-100">
                                I Have Completed the M-Pesa Payment
                            </button>
                        </form>
                        
                    <?php elseif ($order['payment_method'] === 'credit_card'): ?>
                        <h4 class="card-title">Credit/Debit Card Payment</h4>
                        <form id="card-payment-form" class="mt-3">
                            <div class="mb-3">
                                <label for="card_number" class="form-label">Card Number</label>
                                <input type="text" id="card_number" class="form-control" placeholder="1234 5678 9012 3456" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="expiry" class="form-label">Expiry Date</label>
                                    <input type="text" id="expiry" class="form-control" placeholder="MM/YY" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="cvv" class="form-label">CVV</label>
                                    <input type="text" id="cvv" class="form-control" placeholder="123" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Pay KSH <?php echo number_format($total_amount, 2); ?></button>
                        </form>
                    <?php else: // cash_on_delivery ?>
                        <h4 class="card-title">Cash on Delivery</h4>
                        <p>You have selected Cash on Delivery as your payment method.</p>
                        <p>Please have <strong>KSH <?php echo number_format($total_amount, 2); ?></strong> ready when your order arrives.</p>
                        <p>Our delivery person will collect the payment upon delivery.</p>
                        <a href="my_orders.php" class="btn btn-success w-100">View My Orders</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
