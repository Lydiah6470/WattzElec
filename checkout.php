<?php
session_start();
include 'includes/header.php';
include 'includes/db.php';

// Redirect to login if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$cart = $_SESSION['cart'] ?? [];
$total = 0;

// Fetch product details for all items in the cart
$product_ids = array_keys($cart);
if (!empty($product_ids)) {
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    $query = "SELECT p.product_id, p.name, p.price, p.stock_quantity, p.discount,
             CASE 
                WHEN p.discount > 0 THEN p.price * (1 - p.discount/100)
                ELSE p.price 
             END as final_price
             FROM products p WHERE p.product_id IN ($placeholders)";
    $stmt = $conn->prepare($query);
    $stmt->execute($product_ids);

    // Fetch products as an associative array [product_id => row]
    $products = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $products[$row['product_id']] = $row;
    }

    // Calculate total amount
    foreach ($cart as $product_id => $item) {
        $product = $products[$product_id] ?? null;
        if ($product) {
            $quantity = intval($item['quantity']);
            // Check if quantity is more than available stock
            if ($quantity > $product['stock_quantity']) {
                $_SESSION['error_message'] = 'Sorry, only ' . $product['stock_quantity'] . ' units of "' . htmlspecialchars($product['name']) . '" are available.';
                header('Location: cart.php');
                exit;
            }
            $total += $product['final_price'] * $quantity;
        }
    }
} else {
    echo "<p>Your cart is empty. <a href='products.php'>Shop now</a>.</p>";
    include 'includes/footer.php';
    exit;
}
?>

<div class="container-fluid">
    <h2 class="mt-4">Checkout</h2>

    <?php if (empty($cart)): ?>
        <p>Your cart is empty. <a href="products.php">Shop now</a>.</p>
    <?php else: ?>
        <div class="row">
            <!-- Cart Summary -->
            <div class="col-md-8">
                <h4>Order Summary</h4>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart as $product_id => $item): 
                            $product = $products[$product_id] ?? null;
                            if (!$product) continue;

                            $quantity = intval($item['quantity']);
                            $subtotal = $product['final_price'] * $quantity;
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($product['name']) ?></td>
                                <td>
                                    <?php if ($product['discount'] > 0): ?>
                                        <span class="text-decoration-line-through text-muted">
                                            KSH <?= number_format($product['price'], 2) ?>
                                        </span><br>
                                        <span class="text-success fw-bold">
                                            KSH <?= number_format($product['final_price'], 2) ?>
                                        </span>
                                        <span class="badge bg-danger">-<?= $product['discount'] ?>%</span>
                                    <?php else: ?>
                                        <span class="fw-bold">
                                            KSH <?= number_format($product['final_price'], 2) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= $quantity ?>
                                    <?php if ($quantity > ($product['stock_quantity'] - 5)): ?>
                                        <br><small class="text-danger">Only <?= $product['stock_quantity'] ?> left in stock!</small>
                                    <?php endif; ?>
                                </td>
                                <td>KSH <?= number_format($subtotal, 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3">Total</th>
                            <th>KSH <?php echo number_format($total, 2); ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Shipping and Payment Form -->
            <div class="col-md-4">
                <h4>Shipping Details</h4>
                <form method="POST" action="process_checkout.php">
                    <div class="mb-3">
                        <label for="recipient_name" class="form-label">Recipient Name</label>
                        <input type="text" name="recipient_name" id="recipient_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Delivery Address</label>
                        <textarea name="address" id="address" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="city" class="form-label">City</label>
                        <input type="text" name="city" id="city" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="country" class="form-label">Country</label>
                        <input type="text" name="country" id="country" class="form-control" value="Kenya" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" name="phone" id="phone" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="shipping_method" class="form-label">Shipping Method</label>
                        <select name="shipping_method" id="shipping_method" class="form-select" required>
                            <option value="">Select Shipping Method</option>
                            <option value="standard">Standard Delivery (5-7 days) - KSH 500</option>
                            <option value="expedited">Expedited Delivery (2-3 days) - KSH 1,500</option>
                        </select>
                    </div>
                    <h4 class="mt-4">Payment Method</h4>
                    <div class="mb-3">
                        <select name="payment_method" class="form-select" required>
                            <option value="">Select Payment Method</option>
                            <option value="mpesa">M-Pesa</option>
                            <option value="credit_card">Credit/Debit Card</option>
                            <option value="cash_on_delivery">Cash on Delivery</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success w-100">Place Order</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>