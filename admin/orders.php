<?php
session_start();
include 'includes/header.php';
include 'includes/db.php';

// // Redirect to login if the user is not logged in or is not an admin
// if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
//     header("Location: ../login.php");
//     exit;
// }

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_payment_status'])) {
        $order_id = intval($_POST['order_id']);
        $new_payment_status = trim($_POST['payment_status']);

        // Validate the new payment status
        $allowed_payment_statuses = ['pending', 'pending_confirmation', 'completed', 'failed'];

        if (in_array($new_payment_status, $allowed_payment_statuses)) {
            try {
                // Update payment status
                $query = "UPDATE user_order SET payment_status = ? WHERE order_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->execute([$new_payment_status, $order_id]);
                
                $_SESSION['alert'] = ['type' => 'success', 'message' => 'Payment status updated successfully'];
            } catch (PDOException $e) {
                $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Error updating payment status: ' . $e->getMessage()];
            }
        } else {
            $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Invalid payment status value'];
        }
        
        header("Location: orders.php");
        exit;
    }
    
    if (isset($_POST['update_status'])) {
        $order_id = intval($_POST['order_id']);
        $new_status = trim($_POST['status']);

        // Validate the new status
        $allowed_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        if (in_array($new_status, $allowed_statuses)) {
            try {
                // Update order status
                $query = "UPDATE user_order SET status = ? WHERE order_id = ?";
                $stmt = $conn->prepare($query);
                
                // Start transaction before any database modifications
                $conn->beginTransaction();
                
                $stmt->execute([$new_status, $order_id]);

                // If cancelled, restore product stock
                if ($new_status === 'cancelled') {
                    // Get order items
                    $stmt = $conn->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
                    $stmt->execute([$order_id]);
                    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    // Restore stock for each item
                    $updateStock = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE product_id = ?");
                    foreach ($items as $item) {
                        $updateStock->execute([$item['quantity'], $item['product_id']]);
                    }

                    // Update payment status to cancelled
                    $stmt = $conn->prepare("UPDATE user_order SET payment_status = 'cancelled' WHERE order_id = ?");
                    $stmt->execute([$order_id]);
                }

                $conn->commit();
                $_SESSION['alert'] = ['type' => 'success', 'message' => 'Order status updated successfully'];
            } catch (PDOException $e) {
                // Only rollback if a transaction is active
                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }
                $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Error updating order status: ' . $e->getMessage()];
            } catch (Exception $e) {
                // Only rollback if a transaction is active
                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }
                $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Error updating order status: ' . $e->getMessage()];
            }
        } else {
            $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Invalid status value'];
        }
    }
    header("Location: orders.php");
    exit;
}

// Get filter values
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$payment_filter = isset($_GET['payment']) ? $_GET['payment'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Base query
$query = "
    SELECT o.order_id, o.total_amount, o.status, o.payment_status, o.payment_method, o.order_date,
           u.username AS user_name, u.email AS user_email,
           s.recipient_name AS shipping_name, s.address, s.city, s.phone,
           COUNT(oi.order_item_id) as item_count,
           GROUP_CONCAT(DISTINCT CONCAT(p.name, ':::', p.image_1, ':::', oi.quantity) SEPARATOR '|||') as product_details
    FROM user_order o
    JOIN users u ON o.user_id = u.user_id
    LEFT JOIN shipping_detail s ON o.shipping_id = s.shipping_id
    LEFT JOIN order_items oi ON o.order_id = oi.order_id
    LEFT JOIN products p ON oi.product_id = p.product_id
    WHERE 1=1
";

$params = [];

// Add filters
if ($status_filter) {
    $query .= " AND o.status = ?";
    $params[] = $status_filter;
}
if ($payment_filter) {
    $query .= " AND o.payment_status = ?";
    $params[] = $payment_filter;
}
if ($search) {
    $query .= " AND (o.order_id LIKE ? OR u.username LIKE ? OR u.email LIKE ? OR s.recipient_name LIKE ?)"; 
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
}

$query .= " GROUP BY o.order_id ORDER BY o.order_date DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get order statistics
$stats_query = "SELECT 
    COUNT(*) as total_orders,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
    SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_orders,
    SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) as shipped_orders,
    SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
    SUM(CASE WHEN payment_status = 'completed' THEN 1 ELSE 0 END) as paid_orders,
    SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_count
    FROM user_order";
$stats = $conn->query($stats_query)->fetch(PDO::FETCH_ASSOC);

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
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.orders-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    min-width: 1000px;
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

.order-stats {
    display: flex;
    gap: 1rem;
    margin-top: 1rem;
}

.stat-card {
    background: white;
    padding: 1rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    min-width: 150px;
    text-align: center;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--primary-color);
}

.stat-label {
    font-size: 0.9rem;
    color: var(--text-secondary);
    margin-top: 0.25rem;
}

.delivered .stat-value {
    color: var(--success-color);
}

.filters-section {
    margin-bottom: 2rem;
}

.filters-form {
    display: flex;
    gap: 1rem;
    align-items: center;
    flex-wrap: wrap;
}

.filters-form .form-control,
.filters-form .form-select {
    max-width: 200px;
}

.alert {
    margin-bottom: 1rem;
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

.payment-status-pending_confirmation {
    background-color: #cce5ff;
    color: #004085;
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

.product-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    max-width: 300px;
}

.product-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: #f8f9fa;
    padding: 0.5rem;
    border-radius: 6px;
}

.product-thumbnail {
    width: 40px;
    height: 40px;
    object-fit: cover;
    border-radius: 4px;
}

.product-info {
    flex: 1;
    min-width: 0;
}

.product-name {
    font-size: 0.9rem;
    font-weight: 500;
    color: var(--text-primary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.product-quantity {
    font-size: 0.8rem;
    color: var(--text-secondary);
}

.more-items {
    font-size: 0.8rem;
    color: var(--text-secondary);
    text-align: center;
    background: #e9ecef;
    padding: 0.25rem;
    border-radius: 4px;
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
        <?php if (isset($_SESSION['alert'])): ?>
            <div class="alert alert-<?php echo $_SESSION['alert']['type']; ?> alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['alert']['message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['alert']); ?>
        <?php endif; ?>

        <div class="page-header">
            <h2 class="page-title">Manage Orders</h2>
            <div class="order-stats">
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($stats['total_orders']); ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($stats['pending_orders']); ?></div>
                    <div class="stat-label">Pending</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($stats['processing_orders']); ?></div>
                    <div class="stat-label">Processing</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($stats['shipped_orders']); ?></div>
                    <div class="stat-label">Shipped</div>
                </div>
                <div class="stat-card delivered">
                    <div class="stat-value"><?php echo number_format($stats['delivered_count']); ?></div>
                    <div class="stat-label">Delivered</div>
                </div>
            </div>
        </div>

        <div class="filters-section">
            <form method="GET" class="filters-form">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Search orders..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                    <option value="shipped" <?php echo $status_filter === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                    <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                    <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
                <select name="payment" class="form-select">
                    <option value="">All Payments</option>
                    <option value="pending" <?php echo $payment_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="pending_confirmation" <?php echo $payment_filter === 'pending_confirmation' ? 'selected' : ''; ?>>Pending Confirmation</option>
                    <option value="completed" <?php echo $payment_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="failed" <?php echo $payment_filter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                </select>
                <button type="submit" class="btn btn-primary">Filter</button>
                <?php if ($status_filter || $payment_filter || $search): ?>
                    <a href="orders.php" class="btn btn-outline-secondary">Clear Filters</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if (count($orders) > 0): ?>
            <div class="orders-grid">
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Products</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Payment Status</th>
                            <th>Payment Method</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>
                                    <span class="order-id">#<?php echo $order['order_id']; ?></span>
                                </td>
                                <td>
                                    <div class="product-list">
                                        <?php
                                        if (!empty($order['product_details'])) {
                                            $products = explode('|||', $order['product_details']);
                                            foreach ($products as $index => $product) {
                                                if ($index >= 3) {
                                                    echo '<div class="more-items">+' . (count($products) - 3) . ' more</div>';
                                                    break;
                                                }
                                                list($name, $image, $quantity) = explode(':::', $product);
                                                ?>
                                                <div class="product-item">
                                                    <img src="../<?php echo htmlspecialchars($image); ?>" 
                                                         alt="<?php echo htmlspecialchars($name); ?>"
                                                         class="product-thumbnail">
                                                    <div class="product-info">
                                                        <div class="product-name"><?php echo htmlspecialchars($name); ?></div>
                                                        <div class="product-quantity">Qty: <?php echo $quantity; ?></div>
                                                    </div>
                                                </div>
                                                <?php
                                            }
                                        }
                                        ?>
                                    </div>
                                </td>
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
                                    <form method="POST" class="status-form">
                                        <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                        <select name="payment_status" class="status-select">
                                            <option value="pending" <?php echo $order['payment_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="pending_confirmation" <?php echo $order['payment_status'] === 'pending_confirmation' ? 'selected' : ''; ?>>Pending Confirmation</option>
                                            <option value="completed" <?php echo $order['payment_status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                            <option value="failed" <?php echo $order['payment_status'] === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                        </select>
                                        <button type="submit" name="update_payment_status" class="btn btn-primary">Update</button>
                                    </form>
                                    <span class="payment-badge payment-status-<?php echo strtolower($order['payment_status']); ?>">
                                        <?php echo str_replace('_', ' ', ucfirst($order['payment_status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="payment-method"><?php echo htmlspecialchars($order['payment_method']); ?></span>
                                </td>
                                <td>
                                    <span class="order-date"><?php echo date('M j, Y H:i', strtotime($order['order_date'])); ?></span>
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