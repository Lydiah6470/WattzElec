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
$wishlistProducts = [];

try {
    // Fetch wishlist product IDs for this user
    $query = "SELECT w.product_id, p.name, p.image_1, p.price, p.discount, p.stock_quantity,
              p.price * (1 - COALESCE(p.discount, 0)/100) as final_price
              FROM wishlist w 
              JOIN products p ON w.product_id = p.product_id 
              WHERE w.user_id = ? 
              ORDER BY w.added_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute([$user_id]);
    $wishlistProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo "<p class='text-danger'>An error occurred while fetching your wishlist items. Please <a href='wishlist.php'>refresh</a> or contact support if the problem persists.</p>";
    include 'includes/footer.php';
    exit;
}
?>

<div class="container-fluid">
    <h2 class="mt-4">Wishlist</h2>

    <?php if (empty($wishlistProducts)): ?>
        <p>Your wishlist is empty. <a href="products.php">Shop now</a>.</p>
    <?php else: ?>
        <div class="row">
            <?php foreach ($wishlistProducts as $product): 
                // Price is already calculated in the query
            ?>
                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="card h-100">
                        <!-- Product Image -->
                        <img src="<?= htmlspecialchars($product['image_1'] ?? 'images/placeholder.php') ?>" 
                             class="card-img-top" 
                             alt="<?= htmlspecialchars($product['name']) ?>">
                        <div class="card-body d-flex flex-column justify-content-between">
                            <!-- Product Name -->
                            <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                            <!-- Price -->
                            <div class="mb-2">
                                <?php if ($product['discount'] > 0): ?>
                                    <del class="text-muted" style="font-size: 0.9rem;">KSH <?= number_format($product['price'], 2) ?></del>
                                    <span class="badge bg-danger ms-2"><?= $product['discount'] ?>% OFF</span>
                                <?php endif; ?>
                                <div class="mt-1">
                                    <span style="color: var(--primary-color); font-weight: bold; font-size: 1.2rem;">
                                        KSH <?= number_format($product['final_price'], 2) ?>
                                    </span>
                                </div>
                            </div>
                            <!-- Stock Status -->
                            <p class="mb-2">
                                <?php 
                                $stock = $product['stock_quantity'] ?? 0;
                                if ($stock > 10) {
                                    echo '<span class="badge bg-success">In Stock</span>';
                                } elseif ($stock > 0) {
                                    echo '<span class="badge bg-warning text-dark">Low Stock (' . $stock . ' left)</span>';
                                } else {
                                    echo '<span class="badge bg-danger">Out of Stock</span>';
                                }
                                ?>
                            </p>
                            <!-- Action Buttons -->
                            <div class="d-flex justify-content-between align-items-center mt-auto">
                                <form method="POST" action="remove_from_wishlist.php" class="m-0">
                                    <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash me-1"></i> Remove
                                    </button>
                                </form>
                                <a href="add_to_cart.php?product_id=<?= $product['product_id'] ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-shopping-cart me-1"></i> Add to Cart
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
