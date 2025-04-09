<?php
session_start();
require_once 'includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: my_orders.php');
    exit();
}

$order_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Fetch order details
$stmt = $conn->prepare("SELECT o.*, u.email, u.phone, u.address 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.id = ? AND o.user_id = ?");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header('Location: my_orders.php');
    exit();
}

// Fetch order items
$stmt = $conn->prepare("SELECT oi.*, p.name as product_name, p.image 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items = $stmt->get_result();

$page_title = "Order Details #" . $order_id;
include 'includes/header.php';
?>

<div class="container my-4">
    <div class="mb-4">
        <a href="my_orders.php" class="btn btn-link ps-0">
            <i class="fas fa-arrow-left"></i> Back to My Orders
        </a>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Order #<?php echo htmlspecialchars($order_id); ?></h5>
                    <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                        <?php echo htmlspecialchars($order['status']); ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total = 0;
                                while ($item = $items->fetch_assoc()):
                                    $item_total = $item['price'] * $item['quantity'];
                                    $total += $item_total;
                                ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo htmlspecialchars($item['image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                                 class="product-thumbnail me-2">
                                            <?php echo htmlspecialchars($item['product_name']); ?>
                                        </div>
                                    </td>
                                    <td>KSh <?php echo number_format($item['price'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                    <td class="text-end">KSh <?php echo number_format($item_total, 2); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                    <td class="text-end"><strong>KSh <?php echo number_format($total, 2); ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Order Information</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Order Date:</dt>
                        <dd class="col-sm-8"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></dd>

                        <dt class="col-sm-4">Payment Method:</dt>
                        <dd class="col-sm-8"><?php echo ucwords(str_replace('_', ' ', $order['payment_method'])); ?></dd>

                        <dt class="col-sm-4">Payment Status:</dt>
                        <dd class="col-sm-8"><?php echo ucfirst($order['payment_status']); ?></dd>

                        <dt class="col-sm-4">Email:</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($order['email']); ?></dd>

                        <dt class="col-sm-4">Phone:</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($order['phone']); ?></dd>

                        <dt class="col-sm-4">Address:</dt>
                        <dd class="col-sm-8"><?php echo nl2br(htmlspecialchars($order['address'])); ?></dd>
                    </dl>
                </div>
            </div>

            <?php if ($order['status'] === 'pending'): ?>
            <div class="card">
                <div class="card-body">
                    <form action="cancel_order.php" method="post" class="text-center">
                        <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                        <button type="submit" class="btn btn-danger" 
                                onclick="return confirm('Are you sure you want to cancel this order?')">
                            Cancel Order
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.product-thumbnail {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 4px;
}

.card {
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border: none;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

dl.row {
    margin-bottom: 0;
}

dt {
    font-weight: 600;
    color: #6c757d;
}

dd {
    margin-bottom: 0.5rem;
}

.btn-danger {
    transition: all 0.2s ease;
}

.btn-danger:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
</style>

<?php include 'includes/footer.php'; ?>
