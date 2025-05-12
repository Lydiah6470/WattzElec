<?php
session_start();
require_once 'includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user's orders with product info
$stmt = $conn->prepare("SELECT o.order_id, o.order_date, o.total_amount, o.status, o.payment_status,
    COUNT(oi.order_item_id) as item_count
    FROM user_order o 
    LEFT JOIN order_items oi ON o.order_id = oi.order_id 
    WHERE o.user_id = ? 
    GROUP BY o.order_id 
    ORDER BY o.order_date DESC");
$stmt->execute([$user_id]);
$result = $stmt;

$page_title = "My Orders";
include 'includes/header.php';
?>

<div class="container my-4">
    <h1 class="mb-4">My Orders</h1>
    
    <?php 
$orders = $result->fetchAll();
$order_ids = array_column($orders, 'order_id');
$order_items_map = [];
if (count($order_ids) > 0) {
    // Prepare placeholders for the IN clause
    $placeholders = implode(',', array_fill(0, count($order_ids), '?'));
    $stmt_items = $conn->prepare("SELECT oi.order_id, oi.product_id, oi.price, oi.quantity, 
        p.name as product_name, p.image_1 as product_image 
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.product_id 
        WHERE oi.order_id IN ($placeholders)");
    $stmt_items->execute($order_ids);
    while ($item = $stmt_items->fetch()) {
        $order_items_map[$item['order_id']][] = $item;
    }
}
?>
<?php if (count($orders) > 0): ?>
    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle my-orders-table">
            <thead class="table-light">
                <tr>
                    <th>Order #</th>
                    <th>Date</th>
                    <th>Product Image</th>
                    <th>Product Name</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Total (Product)</th>
                    <th>Payment Status</th>
                    <th>Order Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <?php 
                        $items = !empty($order_items_map[$order['order_id']]) ? $order_items_map[$order['order_id']] : [];
                        $rowspan = count($items) > 0 ? count($items) : 1;
                        $first = true;
                        if ($rowspan === 0) $rowspan = 1;
                        if (empty($items)) {
                            // Show a blank row for orders with no items (shouldn't happen)
                            echo '<tr>';
                            echo '<td rowspan="1">#'.htmlspecialchars($order['order_id']).'</td>';
                            echo '<td rowspan="1">'.date('M d, Y', strtotime($order['order_date'])).'</td>';
                            echo '<td colspan="7" class="text-center text-muted">No items</td>';
                            echo '</tr>';
                        } else {
                            foreach ($items as $item):
                    ?>
                    <tr>
                        <?php if ($first): ?>
                            <td rowspan="<?php echo $rowspan; ?>">#<?php echo htmlspecialchars($order['order_id']); ?></td>
                            <td rowspan="<?php echo $rowspan; ?>"><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                        <?php endif; ?>
                        <td><img src="<?php echo htmlspecialchars($item['product_image'] ?? 'images/default.jpg'); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" style="width:48px;height:48px;object-fit:cover;border-radius:6px;"></td>
                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td>KSh <?php echo number_format($item['price'], 2); ?></td>
                        <td>KSh <?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                        <?php if ($first): ?>
                            <td rowspan="<?php echo $rowspan; ?>"><span class="badge bg-<?php echo $order['payment_status'] === 'completed' ? 'success' : ($order['payment_status'] === 'failed' ? 'danger' : 'warning'); ?>"><?php echo ucfirst($order['payment_status']); ?></span></td>
                            <td rowspan="<?php echo $rowspan; ?>">
                                <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                    <?php echo htmlspecialchars($order['status']); ?>
                                </span>
                            </td>
                            <td rowspan="<?php echo $rowspan; ?>" class="text-center">
                                <?php if (in_array($order['status'], ['pending', 'processing'])): ?>
                                    <button class="btn btn-sm btn-outline-danger cancel-order" 
                                            data-order-id="<?php echo $order['order_id']; ?>">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                <?php endif; ?>
                                <a href="view_order.php?order_id=<?php echo $order['order_id']; ?>" 
                                   class="btn btn-sm btn-outline-primary ms-1">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        <?php endif; ?>
                    </tr>
                    <?php $first = false; endforeach; }
                    ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <style>
        .my-orders-table th, .my-orders-table td { vertical-align: middle; }
        .my-orders-table img { box-shadow: 0 1px 4px rgba(0,0,0,0.07); }
        .status-badge {
            padding: 0.4em 1em;
            border-radius: 1rem;
            font-size: 0.95em;
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
        @media (max-width: 768px) {
            .my-orders-table td, .my-orders-table th { font-size: 0.97em; padding: 0.4em; }
            .my-orders-table img { width: 36px; height: 36px; }
        }
    </style>
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

<!-- Cancel Order Modal -->
<div class="modal fade" id="cancelOrderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cancel Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to cancel this order? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Keep Order</button>
                <button type="button" class="btn btn-danger" id="confirmCancel">Yes, Cancel Order</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let cancelOrderModal = new bootstrap.Modal(document.getElementById('cancelOrderModal'));
    let currentOrderId = null;

    // Handle cancel button clicks
    document.querySelectorAll('.cancel-order').forEach(button => {
        button.addEventListener('click', function() {
            currentOrderId = this.dataset.orderId;
            cancelOrderModal.show();
        });
    });

    // Handle confirm cancel
    document.getElementById('confirmCancel').addEventListener('click', function() {
        if (!currentOrderId) return;

        // Send cancel request
        fetch('cancel_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'order_id=' + encodeURIComponent(currentOrderId)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Close modal and reload page
                cancelOrderModal.hide();
                location.reload();
            } else {
                alert(data.error || 'Failed to cancel order');
            }
        })
        .catch(error => {
            alert('An error occurred while cancelling the order');
            console.error('Error:', error);
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
