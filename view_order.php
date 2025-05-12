<?php
session_start();
require_once 'includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

// Fetch order details with items
$query = "SELECT o.*, s.recipient_name, s.address, s.city, s.phone,
          oi.quantity, oi.price as item_price, oi.product_id,
          p.name as product_name, p.image_1 as product_image
          FROM user_order o 
          JOIN shipping_detail s ON o.shipping_id = s.shipping_id 
          JOIN order_items oi ON o.order_id = oi.order_id
          JOIN products p ON oi.product_id = p.product_id
          WHERE o.order_id = ? AND o.user_id = ?";

$stmt = $conn->prepare($query);
$stmt->execute([$order_id, $user_id]);
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($order_items)) {
    header('Location: my_orders.php');
    exit();
}

$order = $order_items[0]; // Use first item for order details
$page_title = "Order Details #" . $order_id;
include 'includes/header.php';
?>

<div class="container my-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Order Details #<?php echo $order_id; ?></h1>
                <a href="my_orders.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Orders
                </a>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Order Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Order Date:</strong> <?php echo date('M d, Y', strtotime($order['order_date'])); ?></p>
                            <p><strong>Order Status:</strong> 
                                <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                    <?php echo htmlspecialchars($order['status']); ?>
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Payment Status:</strong> 
                                <span class="badge bg-<?php echo $order['payment_status'] === 'completed' ? 'success' : ($order['payment_status'] === 'failed' ? 'danger' : 'warning'); ?>">
                                    <?php echo ucfirst($order['payment_status']); ?>
                                </span>
                            </p>
                            <p><strong>Total Amount:</strong> KSH <?php echo number_format($order['total_amount'], 2); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Shipping Details</h5>
                </div>
                <div class="card-body">
                    <p><strong>Recipient Name:</strong> <?php echo htmlspecialchars($order['recipient_name']); ?></p>
                    <p><strong>Address:</strong> <?php echo htmlspecialchars($order['address']); ?></p>
                    <p><strong>City:</strong> <?php echo htmlspecialchars($order['city']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Order Items</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($order_items as $item): ?>
                    <div class="d-flex align-items-center mb-3 border-bottom pb-3">
                        <img src="<?php echo htmlspecialchars($item['product_image']); ?>" 
                             alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                             class="img-thumbnail" style="width: 80px; height: 80px; object-fit: cover;">
                        <div class="ms-3 flex-grow-1">
                            <h6 class="mb-1"><?php echo htmlspecialchars($item['product_name']); ?></h6>
                            <p class="mb-0">Quantity: <?php echo $item['quantity']; ?></p>
                            <p class="mb-0">Price: KSH <?php echo number_format($item['item_price'], 2); ?></p>
                        </div>
                        <div class="text-end">
                            <p class="mb-0"><strong>Subtotal:</strong></p>
                            <p class="mb-0">KSH <?php echo number_format($item['item_price'] * $item['quantity'], 2); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <div class="text-end mt-3">
                        <h5>Total: KSH <?php echo number_format($order['total_amount'], 2); ?></h5>
                    </div>
                </div>
            </div>

            <?php if (in_array($order['status'], ['pending', 'processing'])): ?>
            <div class="text-center mt-4">
                <button class="btn btn-danger cancel-order" data-order-id="<?php echo $order_id; ?>">
                    <i class="fas fa-times"></i> Cancel Order
                </button>
            </div>
            <?php endif; ?>

            <?php if ($order['status'] === 'delivered'): ?>
                <?php foreach ($order_items as $item): ?>
                    <?php
                    // Check if reviews table exists
                    $table_check = $conn->query("SHOW TABLES LIKE 'reviews'");
                    $reviews_table_exists = $table_check->rowCount() > 0;

                    // Create reviews table if it doesn't exist
                    if (!$reviews_table_exists) {
                        $conn->exec("CREATE TABLE IF NOT EXISTS reviews (
                            review_id INT PRIMARY KEY AUTO_INCREMENT,
                            order_id INT,
                            product_id INT,
                            user_id INT,
                            rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
                            comment TEXT,
                            review_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            FOREIGN KEY (order_id) REFERENCES user_order(order_id),
                            FOREIGN KEY (product_id) REFERENCES products(product_id),
                            FOREIGN KEY (user_id) REFERENCES users(user_id)
                        )");
                    }

                    // Check if user has already reviewed this item
                    $review_check = $conn->prepare("SELECT * FROM reviews WHERE order_id = ? AND product_id = ? AND user_id = ?");
                    $review_check->execute([$order_id, $item['product_id'], $user_id]);
                    $existing_review = $review_check->fetch();
                    ?>
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Review for <?php echo htmlspecialchars($item['product_name']); ?></h5>
                        </div>
                        <div class="card-body">
                            <?php if ($existing_review): ?>
                                <div class="mb-3">
                                    <p><strong>Your Rating:</strong></p>
                                    <div class="review-stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?php echo $i <= $existing_review['rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <p><strong>Your Review:</strong></p>
                                    <p><?php echo htmlspecialchars($existing_review['comment']); ?></p>
                                </div>
                            <?php else: ?>
                                <form method="POST" action="submit_review.php" class="review-form">
                                    <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                                    <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                    <div class="mb-3">
                                        <label class="form-label">Rating</label>
                                        <div class="stars">
                                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                                <input type="radio" name="rating" value="<?php echo $i; ?>" id="star<?php echo $i; ?>_<?php echo $item['product_id']; ?>" required>
                                                <label for="star<?php echo $i; ?>_<?php echo $item['product_id']; ?>" class="star-label">
                                                    <i class="far fa-star"></i>
                                                </label>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="comment_<?php echo $item['product_id']; ?>" class="form-label">Review Comment</label>
                                        <textarea name="comment" id="comment_<?php echo $item['product_id']; ?>" class="form-control" rows="3" required></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Submit Review</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

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
                // Redirect to orders page
                window.location.href = 'my_orders.php';
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

<style>
/* Star Rating Styles */
.stars {
    display: inline-flex;
    flex-direction: row-reverse;
    gap: 0.25rem;
    font-size: 1.5rem;
}

.stars input[type="radio"] {
    display: none;
}

.star-label {
    cursor: pointer;
    color: #ccc;
    transition: color 0.2s;
}

.star-label:hover,
.star-label:hover ~ .star-label,
.stars input[type="radio"]:checked ~ .star-label {
    color: #ffc107;
}

.stars .fa-star.text-warning {
    color: #ffc107;
}

.stars .fa-star.text-muted {
    color: #ccc;
}

/* Existing review stars */
.review-stars {
    display: inline-flex;
    gap: 0.25rem;
    font-size: 1.5rem;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    display: inline-block;
}

.status-pending {
    background-color: var(--status-pending-bg, #fff3cd);
    color: var(--status-pending-color, #856404);
}

.status-processing {
    background-color: var(--status-processing-bg, #cce5ff);
    color: var(--status-processing-color, #004085);
}

.status-shipped {
    background-color: var(--status-shipped-bg, #d4edda);
    color: var(--status-shipped-color, #155724);
}

.status-delivered {
    background-color: var(--status-delivered-bg, #d1e7dd);
    color: var(--status-delivered-color, #0f5132);
}

.status-cancelled {
    background-color: var(--status-cancelled-bg, #f8d7da);
    color: var(--status-cancelled-color, #721c24);
}
</style>

<?php include 'includes/footer.php'; ?>
