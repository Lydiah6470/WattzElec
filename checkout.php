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
    $query = "SELECT id, name, price, discount FROM products WHERE id IN ($placeholders)";
    $stmt = $conn->prepare($query);
    $stmt->execute($product_ids);

    // Fetch products as an associative array [id => row]
    $products = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $products[$row['id']] = $row;
    }

    // Calculate total amount
    foreach ($cart as $product_id => $item) {
        $product = $products[$product_id] ?? null;
        if ($product) {
            $originalPrice = $product['price'];
            $discountPercentage = $product['discount'] ?? 0;
            $discountedPrice = $originalPrice - ($originalPrice * ($discountPercentage / 100));
            $quantity = intval($item['quantity']);
            $total += $discountedPrice * $quantity;
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

                            $originalPrice = $product['price'];
                            $discountPercentage = $product['discount'] ?? 0;
                            $discountedPrice = $originalPrice - ($originalPrice * ($discountPercentage / 100));
                            $quantity = intval($item['quantity']);
                            $subtotal = $discountedPrice * $quantity;
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td>KSH <?php echo number_format($discountedPrice, 2); ?></td>
                                <td><?php echo $quantity; ?></td>
                                <td>KSH <?php echo number_format($subtotal, 2); ?></td>
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
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" name="full_name" id="full_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea name="address" id="address" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="city" class="form-label">City</label>
                        <input type="text" name="city" id="city" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" name="phone" id="phone" class="form-control" required>
                    </div>
                    <h4 class="mt-4">Payment Method</h4>
                    <div class="mb-3">
                        <select name="payment_method" class="form-select" required>
                            <option value="">Select Payment Method</option>
                            <option value="credit_card">Credit Card</option>
                            <<option value="credit_card">M-pesa</option>
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