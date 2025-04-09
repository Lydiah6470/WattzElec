<?php
session_start();
require_once 'includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user's orders
$stmt = $conn->prepare("SELECT o.id as order_id, o.created_at as order_date, o.total_amount, o.status,
    COUNT(oi.id) as item_count
    FROM orders o 
    LEFT JOIN order_items oi ON o.id = oi.order_id 
    WHERE o.user_id = ? 
    GROUP BY o.id 
    ORDER BY o.created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$page_title = "My Orders";
include 'includes/header.php';
?>

<div class="container my-4">
    <h1 class="mb-4">My Orders</h1>
    
    <?php if ($result->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="thead-light">
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Items</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($order = $result->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo htmlspecialchars($order['order_id']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                            <td><?php echo htmlspecialchars($order['item_count']); ?> items</td>
                            <td>KSh <?php echo number_format($order['total_amount'], 2); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                    <?php echo htmlspecialchars($order['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="view_my_order.php?id=<?php echo $order['order_id']; ?>" 
                                   class="btn btn-sm btn-primary">View Details</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">📦</div>
            <h3>No Orders Yet</h3>
            <p>You haven't placed any orders yet.</p>
            <a href="products.php" class="btn btn-primary">Start Shopping</a>
        </div>
    <?php endif; ?>
</div>

<style>
:root {
    --status-pending-bg: #fff3cd;
    --status-pending-color: #856404;
    --status-processing-bg: #cce5ff;
    --status-processing-color: #004085;
    --status-shipped-bg: #d4edda;
    --status-shipped-color: #155724;
    --status-delivered-bg: #d1e7dd;
    --status-delivered-color: #0f5132;
    --status-cancelled-bg: #f8d7da;
    --status-cancelled-color: #721c24;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    display: inline-block;
}

.status-pending {
    background-color: var(--status-pending-bg);
    color: var(--status-pending-color);
}

.status-processing {
    background-color: var(--status-processing-bg);
    color: var(--status-processing-color);
}

.status-shipped {
    background-color: var(--status-shipped-bg);
    color: var(--status-shipped-color);
}

.status-delivered {
    background-color: var(--status-delivered-bg);
    color: var(--status-delivered-color);
}

.status-cancelled {
    background-color: var(--status-cancelled-bg);
    color: var(--status-cancelled-color);
}

.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    background: #f8f9fa;
    border-radius: 8px;
    margin: 2rem 0;
}

.empty-state-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.table {
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border-radius: 8px;
    overflow: hidden;
}

.table thead th {
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
}

.table tbody tr:hover {
    background-color: rgba(0,0,0,0.02);
    transition: background-color 0.2s ease;
}

.btn-sm {
    transition: all 0.2s ease;
}

.btn-sm:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
</style>

<?php include 'includes/footer.php'; ?>
