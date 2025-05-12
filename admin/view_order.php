<?php
session_start();
include 'includes/header.php';
include 'includes/db.php';

// Redirect to login if the user is not logged in or is not an admin
// if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
//     header("Location: ../login.php");
//     exit;
// }

// Get order ID from URL
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
if ($order_id === 0) {
    echo "<p class='text-center text-danger'>Invalid order ID.</p>";
    include 'includes/footer.php';
    exit;
}

// Fetch order details
$query = "
    SELECT o.order_id, o.total_amount, o.payment_method, o.payment_status, o.status, o.order_date,
           u.username AS user_name, u.email AS user_email,
           s.recipient_name AS shipping_name, s.address, s.city, s.phone
    FROM user_order o
    JOIN users u ON o.user_id = u.user_id
    LEFT JOIN shipping_detail s ON o.shipping_id = s.shipping_id
    WHERE o.order_id = ?
";
$stmt = $conn->prepare($query);
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo "<p class='text-center text-danger'>Order not found.</p>";
    include 'includes/footer.php';
    exit;
}

// Fetch order items
$itemsQuery = "
    SELECT p.name AS product_name, oi.quantity, oi.price 
    FROM order_items oi
    JOIN products p ON oi.product_id = p.product_id
    WHERE oi.order_id = ?
";
$itemsStmt = $conn->prepare($itemsQuery);
$itemsStmt->execute([$order_id]);
$orderItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
:root {
    --primary-color: #4361ee;
    --success-color: #2ecc71;
    --warning-color: #f1c40f;
    --danger-color: #e74c3c;
    --info-color: #3498db;
    --text-primary: #2d3436;
    --text-secondary: #636e72;
    --background-light: #f8f9fa;
    --border-color: #e9ecef;
}

.order-details-container {
    padding: 2rem;
    background-color: var(--background-light);
    min-height: 100vh;
}

.content-wrapper {
    max-width: 1200px;
    margin: 0 auto;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.page-title {
    font-size: 2rem;
    color: var(--text-primary);
    margin: 0;
}

.back-link {
    color: var(--text-secondary);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: color 0.2s ease;
}

.back-link:hover {
    color: var(--primary-color);
}

.order-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 2rem;
}

@media (max-width: 992px) {
    .order-grid {
        grid-template-columns: 1fr;
    }
}

.order-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

.order-card-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-color);
    background-color: var(--background-light);
}

.order-card-title {
    font-size: 1.25rem;
    color: var(--text-primary);
    margin: 0;
    font-weight: 600;
}

.order-card-body {
    padding: 1.5rem;
}

.info-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.info-item {
    display: flex;
    justify-content: space-between;
    padding: 1rem 0;
    border-bottom: 1px solid var(--border-color);
}

.info-item:last-child {
    border-bottom: none;
}

.info-label {
    color: var(--text-secondary);
    font-weight: 500;
}

.info-value {
    color: var(--text-primary);
    font-weight: 600;
}

.status-badge {
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
}

.status-pending {
    background-color: #fff3cd;
    color: #856404;
}

.status-processing {
    background-color: #cce5ff;
    color: #004085;
}

.status-shipped {
    background-color: #d1ecf1;
    color: #0c5460;
}

.status-delivered {
    background-color: #d4edda;
    color: #155724;
}

.status-cancelled {
    background-color: #f8d7da;
    color: #721c24;
}

.payment-badge {
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
}

.payment-status-paid {
    background-color: #d4edda;
    color: #155724;
}

.payment-status-pending {
    background-color: #fff3cd;
    color: #856404;
}

.payment-status-failed {
    background-color: #f8d7da;
    color: #721c24;
}

.items-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    margin-top: 1rem;
}

.items-table th,
.items-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid var(--border-color);
}

.items-table th {
    background-color: var(--background-light);
    color: var(--text-primary);
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
}

.items-table tbody tr {
    transition: background-color 0.2s ease;
}

.items-table tbody tr:hover {
    background-color: var(--background-light);
}

.order-total {
    display: flex;
    justify-content: flex-end;
    padding: 1rem;
    background-color: var(--background-light);
    border-top: 1px solid var(--border-color);
    font-weight: 600;
}

.shipping-address {
    margin-top: 1rem;
    padding: 1rem;
    background-color: var(--background-light);
    border-radius: 8px;
}

.customer-info {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.customer-name {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--text-primary);
}

.customer-email {
    color: var(--text-secondary);
}

@media (max-width: 768px) {
    .order-details-container {
        padding: 1rem;
    }

    .page-header {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }

    .info-item {
        flex-direction: column;
        gap: 0.5rem;
    }

    .items-table {
        display: block;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
}
</style>

<div class="order-details-container">
    <div class="content-wrapper">
        <div class="page-header">
            <h2 class="page-title">Order Details #<?php echo htmlspecialchars($order['order_id']); ?></h2>
            <a href="orders.php" class="back-link">← Back to Orders</a>
        </div>

        <div class="order-grid">
            <!-- Order Summary -->
            <div class="order-card">
                <div class="order-card-header">
                    <h3 class="order-card-title">Order Summary</h3>
                </div>
                <div class="order-card-body">
                    <ul class="info-list">
                        <li class="info-item">
                            <span class="info-label">Order ID</span>
                            <span class="info-value">#<?php echo htmlspecialchars($order['order_id']); ?></span>
                        </li>
                        <li class="info-item">
                            <span class="info-label">Total Amount</span>
                            <span class="info-value">KSH <?php echo number_format($order['total_amount'], 2); ?></span>
                        </li>
                        <li class="info-item">
                            <span class="info-label">Payment Method</span>
                            <span class="info-value"><?php echo htmlspecialchars($order['payment_method']); ?></span>
                        </li>
                        <li class="info-item">
                            <span class="info-label">Payment Status</span>
                            <span class="payment-badge payment-status-<?php echo strtolower($order['payment_status']); ?>">
                                <?php echo ucfirst($order['payment_status']); ?>
                            </span>
                        </li>
                        <li class="info-item">
                            <span class="info-label">Order Status</span>
                            <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </li>
                        <li class="info-item">
                            <span class="info-label">Order Date</span>
                            <span class="info-value"><?php echo date('M j, Y H:i', strtotime($order['order_date'])); ?></span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Customer Information -->
            <div class="order-card">
                <div class="order-card-header">
                    <h3 class="order-card-title">Customer Information</h3>
                </div>
                <div class="order-card-body">
                    <div class="customer-info">
                        <span class="customer-name"><?php echo htmlspecialchars($order['user_name']); ?></span>
                        <span class="customer-email"><?php echo htmlspecialchars($order['user_email']); ?></span>
                    </div>

                    <div class="shipping-address">
                        <h4>Shipping Address</h4>
                        <p><?php echo htmlspecialchars($order['shipping_name'] ?? 'N/A'); ?></p>
                        <p><?php echo htmlspecialchars($order['address'] ?? 'N/A'); ?></p>
                        <p><?php echo htmlspecialchars($order['city'] ?? 'N/A'); ?></p>
                        <p>Phone: <?php echo htmlspecialchars($order['phone'] ?? 'N/A'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Order Items -->
            <div class="order-card" style="grid-column: 1 / -1;">
                <div class="order-card-header">
                    <h3 class="order-card-title">Order Items</h3>
                </div>
                <div class="order-card-body">
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orderItems as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                    <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                    <td>KSH <?php echo number_format($item['price'], 2); ?></td>
                                    <td>KSH <?php echo number_format($item['quantity'] * $item['price'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="order-total">
                        Total: KSH <?php echo number_format($order['total_amount'], 2); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>