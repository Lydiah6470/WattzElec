<?php
session_start();
include 'includes/header.php';
include 'includes/db.php';

// Redirect to login if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Initialize the cart if not already set
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    echo "<p>Your cart is empty. <a href='products.php'>Shop now</a>.</p>";
    include 'includes/footer.php';
    exit;
}

// Handle actions (e.g., update quantity or remove item)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_cart'])) {
        // Update quantities
        foreach ($_POST['quantity'] as $product_id => $quantity) {
            if ($quantity > 0) {
                $_SESSION['cart'][$product_id]['quantity'] = intval($quantity);
            } else {
                unset($_SESSION['cart'][$product_id]); // Remove item if quantity is 0
            }
        }
    } elseif (isset($_POST['remove_item'])) {
        // Remove specific item
        $product_id = intval($_POST['product_id']);
        unset($_SESSION['cart'][$product_id]);
    }
}

// Fetch product details for all items in the cart
$product_ids = array_keys($_SESSION['cart']);
$placeholders = implode(',', array_fill(0, count($product_ids), '?'));
$query = "SELECT id, name, image_url, price, discount FROM products WHERE id IN ($placeholders)";
$stmt = $conn->prepare($query);
$stmt->execute($product_ids);

// Fetch products as an associative array [id => row]
$products = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $products[$row['id']] = $row;
}
?>

<div class="container-fluid">
    <h2 class="mt-4">Shopping Cart</h2>

    <?php if (empty($_SESSION['cart'])): ?>
        <p>Your cart is empty. <a href="products.php">Shop now</a>.</p>
    <?php else: ?>
        <form method="POST" class="cart-form">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Image</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Total</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $total = 0;
                    foreach ($_SESSION['cart'] as $product_id => $item): 
                        $product = $products[$product_id] ?? null;

                        if (!$product) {
                            echo "<tr><td colspan='6'>Product with ID $product_id not found.</td></tr>";
                            continue;
                        }

                        $originalPrice = $product['price'];
                        $discountPercentage = $product['discount'] ?? 0;
                        $discountedPrice = $originalPrice - ($originalPrice * ($discountPercentage / 100));
                        $quantity = intval($item['quantity']);
                        $subtotal = $discountedPrice * $quantity;
                        $total += $subtotal;
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($product['name'] ?? 'Unknown Product'); ?></td>
                            <td><img src="<?php echo htmlspecialchars($product['image_url'] ?? 'images/default.jpg'); ?>" alt="Product Image" class="cart-image"></td>
                            <td>KSH <?php echo number_format($discountedPrice, 2); ?></td>
                            <td>
                                <!-- Add data attributes for price and product ID -->
                                <input type="number" name="quantity[<?php echo $product_id; ?>]" value="<?php echo $quantity; ?>" min="0" class="form-control quantity-input" style="width: 80px;" data-price="<?php echo $discountedPrice; ?>" data-product-id="<?php echo $product_id; ?>">
                            </td>
                            <td class="subtotal" data-subtotal="<?php echo $subtotal; ?>">KSH <?php echo number_format($subtotal, 2); ?></td>
                            <td>
                                <button type="submit" name="remove_item" value="1" class="btn btn-danger btn-sm">
                                    <i class="fas fa-trash"></i> Remove
                                </button>
                                <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="4">Total</th>
                        <th id="grand-total">KSH <?php echo number_format($total, 2); ?></th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>

            <div class="d-flex justify-content-between align-items-center">
                <button type="submit" name="update_cart" class="btn btn-primary">Update Cart</button>
                <a href="checkout.php" class="btn btn-success">Proceed to Checkout</a>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Function to calculate the subtotal and update the total
    function updateTotals() {
        let total = 0;

        // Loop through all rows in the cart
        document.querySelectorAll('.quantity-input').forEach(input => {
            const quantity = parseInt(input.value) || 0; // Get the current quantity
            const price = parseFloat(input.dataset.price); // Get the discounted price
            const row = input.closest('tr'); // Get the row containing this input

            if (quantity > 0 && price) {
                const subtotal = quantity * price; // Calculate the subtotal
                row.querySelector('.subtotal').textContent = `KSH ${subtotal.toFixed(2)}`; // Update the subtotal cell
                total += subtotal; // Add to the grand total
            } else {
                row.querySelector('.subtotal').textContent = 'KSH 0.00'; // Handle invalid or zero quantity
            }
        });

        // Update the grand total
        document.querySelector('#grand-total').textContent = `KSH ${total.toFixed(2)}`;
    }

    // Attach event listeners to quantity inputs
    document.querySelectorAll('.quantity-input').forEach(input => {
        input.addEventListener('input', updateTotals);
    });

    // Initial calculation on page load
    updateTotals();
});
</script>

<?php include 'includes/footer.php'; ?>