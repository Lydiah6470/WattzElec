<?php
session_start();

// Redirect to login if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href = 'login.php';</script>";
    exit;
}

include 'includes/db.php';
include 'includes/header.php';

// Initialize cart if not set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cart_updated = false;
    
    // Handle remove item
    if (isset($_POST['remove_item']) && isset($_POST['product_id'])) {
        $product_id = intval($_POST['product_id']);
        if (isset($_SESSION['cart'][$product_id])) {
            unset($_SESSION['cart'][$product_id]);
            $removeCart = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
            $removeCart->execute([$_SESSION['user_id'], $product_id]);
            $cart_updated = true;
        }
    }
    
    // Handle quantity updates
    if (isset($_POST['update_cart']) && isset($_POST['quantity'])) {
        foreach ($_POST['quantity'] as $product_id => $quantity) {
            $product_id = intval($product_id);
            $quantity = intval($quantity);
            
            if ($quantity > 0 && isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id]['quantity'] = $quantity;
                $updateCart = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
                $updateCart->execute([$quantity, $_SESSION['user_id'], $product_id]);
                $cart_updated = true;
            } elseif ($quantity <= 0 && isset($_SESSION['cart'][$product_id])) {
                unset($_SESSION['cart'][$product_id]);
                $removeCart = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
                $removeCart->execute([$_SESSION['user_id'], $product_id]);
                $cart_updated = true;
            }
        }
    }
    
    // If cart was updated, refresh the page
    if ($cart_updated) {
        $cart_count = count($_SESSION['cart']);
        echo "<script>
            var badge = document.querySelector('.fa-shopping-cart').nextElementSibling;
            if (badge) {
                badge.textContent = '$cart_count';
                if ($cart_count == 0) badge.style.display = 'none';
            } else if ($cart_count > 0) {
                var span = document.createElement('span');
                span.className = 'badge bg-danger';
                span.textContent = '$cart_count';
                document.querySelector('.fa-shopping-cart').parentNode.appendChild(span);
            }
            window.location.href = '" . $_SERVER['PHP_SELF'] . "';
        </script>";
        exit;
    }
}


// Check if cart is empty
if (empty($_SESSION['cart'])) {
    echo "<div class='container mt-4'><p>Your cart is empty. <a href='products.php'>Shop now</a>.</p></div>";
    include 'includes/footer.php';
    exit;
}

// Initialize variables
$order_success = false;
$order_error = '';

// Handle place order
if (isset($_POST['place_order'])) {
    $user_id = $_SESSION['user_id'];
    $cart = $_SESSION['cart'];
    
    try {
        $product_ids = array_keys($cart);
        $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
        $query = "SELECT product_id, name, price, discount, final_price, stock_quantity 
                 FROM products WHERE product_id IN ($placeholders)";
        $stmt = $conn->prepare($query);
        $stmt->execute($product_ids);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Validate stock and calculate total
        $total = 0;
        $stock_error = false;
        foreach ($products as $product) {
            $quantity = $cart[$product['product_id']]['quantity'];
            if ($quantity > $product['stock_quantity']) {
                $stock_error = true;
                $order_error = "Sorry, " . htmlspecialchars($product['name']) . " only has " . $product['stock_quantity'] . " items in stock.";
                break;
            }
            $total += $product['final_price'] * $quantity;
        }
        
        if (!$stock_error) {
            $conn->beginTransaction();
            // Insert order (no shipping info, minimal fields)
            $main_product_id = $product_ids[0] ?? null;
            // Insert a default shipping_detail if needed
$default_shipping_id = 1; // Change this if your default shipping_detail id is different

// Optional: Check if the default shipping_detail exists
$checkShipping = $conn->prepare("SELECT shipping_id FROM shipping_detail WHERE shipping_id = ?");
$checkShipping->execute([$default_shipping_id]);
if (!$checkShipping->fetch()) {
    // Insert a dummy shipping record if not found
    $conn->prepare("INSERT INTO shipping_detail (shipping_id, full_name, address, city, phone) VALUES (?, 'TBD', 'TBD', 'TBD', 'TBD')")->execute([$default_shipping_id]);
}

$orderQuery = "INSERT INTO orders (user_id, product_id, total_amount, payment_method, status, shipping_id, order_date) VALUES (?, ?, ?, ?, 'pending', ?, CURRENT_TIMESTAMP)";
$orderStmt = $conn->prepare($orderQuery);
$orderStmt->execute([$user_id, $main_product_id, $total, 'N/A', $default_shipping_id]);
                $order_id = $conn->lastInsertId();
                // Insert order items
                    $itemQuery = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
                    $itemStmt = $conn->prepare($itemQuery);
                    
                    // Update stock and insert order items
                    $updateStockQuery = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ?";
                    $updateStockStmt = $conn->prepare($updateStockQuery);
                    
                    foreach ($cart as $product_id => $item) {
                        $product = $products[$product_id] ?? null;
                        if (!$product) continue;
                        
                        $quantity = intval($item['quantity']);
                        $itemStmt->execute([$order_id, $product_id, $quantity, $product['final_price']]);
                        
                        // Update stock
                        $updateStockStmt->execute([$quantity, $product_id]);
                }
                $conn->commit();
                unset($_SESSION['cart']);
                $order_success = true;
                // Use JavaScript redirect instead of header() since we're past output
                echo '<script>window.location.href = "my_orders.php";</script>';
                exit;
        }
    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $order_error = "An error occurred while placing your order: " . htmlspecialchars($e->getMessage());
    }
}

// Fetch product details for all items in the cart
$product_ids = array_keys(isset($_SESSION['cart']) && is_array($_SESSION['cart']) ? $_SESSION['cart'] : []);
$products = [];
if (!empty($product_ids)) {
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    $query = "SELECT product_id, name, image_1, price, discount, stock_quantity, final_price
             FROM products WHERE product_id IN ($placeholders)";
    $stmt = $conn->prepare($query);
    $stmt->execute($product_ids);
    // Fetch products as an associative array [product_id => row]
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $products[$row['product_id']] = $row;
        // Always use the current final_price when displaying the cart
        $_SESSION['cart'][$row['product_id']]['price'] = $row['final_price'];
        if (!isset($_SESSION['cart'][$row['product_id']]['price'])) {
            $_SESSION['cart'][$row['product_id']]['price'] = $row['final_price'];
        }
    }
}

?>

<div class="container-fluid">
    <h2 class="mt-4">Shopping Cart</h2>

    <?php if ($order_success): ?>
        <div class="alert alert-success mt-4">Order placed successfully! <a href="my_orders.php" class="alert-link">View your orders</a>.</div>
    <?php elseif ($order_error): ?>
        <div class="alert alert-danger mt-4"><?php echo $order_error; ?></div>
    <?php endif; ?>

    <?php if (empty($_SESSION['cart']) && !$order_success): ?>
        <p>Your cart is empty. <a href="products.php">Shop now</a>.</p>
    <?php elseif (!$order_success): ?>
        <div class="table-responsive">
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

                        if (!$product) continue;
                        
                        // Use the price stored in the cart session, fallback to current price if not set
                        $storedPrice = isset($item['price']) ? floatval($item['price']) : $product['final_price'];
                        // Update the stored price if it's not set
                        if (!isset($item['price'])) {
                            $_SESSION['cart'][$product_id]['price'] = $product['final_price'];
                        }
                        $quantity = intval($item['quantity']);
                        $subtotal = $storedPrice * $quantity;
                        $total += $subtotal;
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td><img src="<?php echo htmlspecialchars($product['image_1']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="max-width: 100px;"></td>
                            <td>
                                <?php if ($product['discount'] > 0): ?>
                                    <del class="text-muted">KSH <?php echo number_format($product['price'], 2); ?></del><br>
                                    <span class="badge bg-danger"><?php echo $product['discount']; ?>% OFF</span><br>
                                <?php endif; ?>
                                <strong>KSH <?php echo number_format($product['final_price'], 2); ?></strong>
                            </td>
                            <td>
                                <form method="POST" class="quantity-form" style="display: inline-block;">
                                    <input type="number" name="quantity[<?php echo $product_id; ?>]" 
                                           value="<?php echo $quantity; ?>" 
                                           min="0" max="<?php echo $product['stock_quantity']; ?>" 
                                           class="form-control quantity-input" 
                                           style="width: 80px;" 
                                           data-price="<?php echo $storedPrice; ?>" 
                                           data-product-id="<?= $product_id ?>">
                                    <?php if ($product['stock_quantity'] <= 5): ?>
                                        <small class="text-danger">Only <?= $product['stock_quantity'] ?> left!</small>
                                    <?php endif; ?>
                                    <button type="submit" name="update_cart" class="btn btn-primary btn-sm mt-1">Update</button>
                                </form>
                            </td>
                            <td class="subtotal" data-subtotal="<?php echo $subtotal; ?>">KSH <?php echo number_format($subtotal, 2); ?></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                    <button type="submit" name="remove_item" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i> Remove
                                    </button>
                                </form>
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

            <div class="d-flex justify-content-end align-items-center mt-3">
                <a href="checkout.php" class="btn btn-success">Proceed to Checkout</a>

            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Function to format currency
    function formatCurrency(amount) {
        return 'KSH ' + parseFloat(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    // Function to calculate the subtotal and update the total
    function updateTotals() {
        let total = 0;

        // Loop through all rows in the cart
        document.querySelectorAll('.quantity-input').forEach(input => {
            const quantity = parseInt(input.value) || 0;
            const price = parseFloat(input.dataset.price) || 0;
            const row = input.closest('tr');
            const subtotalElement = row.querySelector('.subtotal');

            if (quantity > 0 && price) {
                const subtotal = quantity * price;
                subtotalElement.textContent = formatCurrency(subtotal);
                subtotalElement.dataset.subtotal = subtotal;
                total += subtotal;
            } else {
                subtotalElement.textContent = formatCurrency(0);
                subtotalElement.dataset.subtotal = 0;
            }

            // Update the data attribute for the quantity input
            input.dataset.lastValidQuantity = quantity || 1;
        });

        // Update the grand total
        document.querySelector('#grand-total').textContent = formatCurrency(total);
    }

    // Attach event listeners to quantity inputs
    document.querySelectorAll('.quantity-input').forEach(input => {
        const maxStock = parseInt(input.getAttribute('max'));
        
        input.addEventListener('input', function(e) {
            let value = parseInt(this.value) || 0;
            
            // Ensure the value is within valid range
            if (value < 0) {
                value = 0;
            } else if (value > maxStock) {
                value = maxStock;
                alert('Only ' + maxStock + ' items available in stock.');
            }
            
            this.value = value || '';
            updateTotals();
        });

        // Handle invalid input
        input.addEventListener('blur', function() {
            if (!this.value || parseInt(this.value) === 0) {
                this.value = 1;
                updateTotals();
            }
        });
    });

    // Initial calculation on page load
    updateTotals();
});
</script>

<?php include 'includes/footer.php'; ?>