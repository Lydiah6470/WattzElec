<?php
session_start();
include 'includes/header.php';
include 'includes/db.php';

// Redirect to login if the user is not logged in or is not an admin
// if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
//     header("Location: ../login.php");
//     exit;
// }

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = trim($_POST['status']);

    // Validate the new status
    $allowed_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    if (in_array($new_status, $allowed_statuses)) {
        $query = "UPDATE orders SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$new_status, $order_id]);

        if ($stmt->rowCount() > 0) {
            // Redirect back to the same page to reflect the changes
            header("Location: orders.php");
            exit;
        } else {
            echo "<div class='alert alert-warning'>No changes were made to the order status.</div>";
        }
    } else {
        echo "<div class='alert alert-danger'>Invalid status value.</div>";
    }
}

// Fetch all orders with user and shipping details
$query = "
    SELECT o.id AS order_id, o.total_amount, o.status, o.payment_status, o.payment_method, o.created_at,
           u.name AS user_name, u.email AS user_email,
           s.full_name AS shipping_name, s.address, s.city, s.phone
    FROM orders o
    JOIN users u ON o.user_id = u.id
    LEFT JOIN shipping_details s ON o.shipping_id = s.id
    ORDER BY o.created_at DESC
";
$stmt = $conn->prepare($query);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($orders)) {
    echo "<p class='text-center text-muted'>No orders found.</p>";
    include 'includes/footer.php';
    exit;
}
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

.order-container {
    padding: 2rem;
    background-color: var(--background-light);
    min-height: 100vh;
}

.content-wrapper {
    max-width: 1400px;
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

.orders-grid {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

.orders-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.orders-table th,
.orders-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid var(--border-color);
}

.orders-table th {
    background-color: var(--background-light);
    color: var(--text-primary);
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
}

.orders-table tbody tr {
    transition: background-color 0.2s ease;
}

.orders-table tbody tr:hover {
    background-color: var(--background-light);
}

.customer-info {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.customer-info .name {
    font-weight: 500;
    color: var(--text-primary);
}

.customer-info .email {
    color: var(--text-secondary);
    font-size: 0.9rem;
}

.status-badge {
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
    display: inline-block;
    margin-top: 0.5rem;
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

.status-form {
    display: flex;
    gap: 0.5rem;
    align-items: center;
    margin-bottom: 0.5rem;
}

.status-form select {
    padding: 0.4rem;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    font-size: 0.9rem;
    background-color: white;
}

.btn {
    padding: 0.5rem 1rem;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    font-size: 0.9rem;
    transition: all 0.2s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.btn-primary {
    background-color: var(--primary-color);
    color: white;
}

.btn-info {
    background-color: var(--info-color);
    color: white;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.order-amount {
    font-weight: 600;
    color: var(--text-primary);
}

.order-date {
    color: var(--text-secondary);
    font-size: 0.9rem;
}

.payment-method {
    text-transform: capitalize;
    color: var(--text-primary);
}

@media (max-width: 1024px) {
    .orders-table {
        display: block;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
}

@media (max-width: 768px) {
    .order-container {
        padding: 1rem;
    }

    .page-header {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }

    .status-form {
        flex-direction: column;
        align-items: stretch;
    }

    .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<div class="order-container">
    <div class="content-wrapper">
        <div class="page-header">
            <h2 class="page-title">Manage Orders</h2>
        </div>

        <?php if (count($orders) > 0): ?>
            <div class="orders-grid">
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Method</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>#<?php echo htmlspecialchars($order['order_id']); ?></td>
                                <td>
                                    <div class="customer-info">
                                        <span class="name"><?php echo htmlspecialchars($order['user_name']); ?></span>
                                        <span class="email"><?php echo htmlspecialchars($order['user_email']); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="order-amount">KSH <?php echo number_format($order['total_amount'], 2); ?></span>
                                </td>
                                <td>
                                    <form method="POST" class="status-form">
                                        <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                        <select name="status" class="status-select">
                                            <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                            <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                            <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                            <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                        <button type="submit" name="update_status" class="btn btn-primary">Update</button>
                                    </form>
                                    <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="payment-badge payment-status-<?php echo strtolower($order['payment_status']); ?>">
                                        <?php echo ucfirst($order['payment_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="payment-method"><?php echo htmlspecialchars($order['payment_method']); ?></span>
                                </td>
                                <td>
                                    <span class="order-date"><?php echo date('M j, Y H:i', strtotime($order['created_at'])); ?></span>
                                </td>
                                <td>
                                    <a href="view_order.php?order_id=<?php echo $order['order_id']; ?>" class="btn btn-info">View Details</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p>No orders found.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>