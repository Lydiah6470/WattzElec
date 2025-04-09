<?php
session_start();
include 'includes/header.php';
include 'includes/db.php';

// Redirect to login if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Initialize the wishlist if not already set
if (!isset($_SESSION['wishlist']) || !is_array($_SESSION['wishlist']) || empty($_SESSION['wishlist'])) {
    echo "<p>Your wishlist is empty. <a href='products.php'>Shop now</a>.</p>";
    include 'includes/footer.php';
    exit;
}

$product_ids = $_SESSION['wishlist'];

// Ensure there are valid product IDs
if (empty($product_ids)) {
    echo "<p>Your wishlist is empty. <a href='products.php'>Shop now</a>.</p>";
    include 'includes/footer.php';
    exit;
}

try {
    // Generate placeholders dynamically
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    $query = "SELECT id, name, image_url, price, discount FROM products WHERE id IN ($placeholders)";
    $stmt = $conn->prepare($query);

    // Log the query and parameters for debugging
    error_log("Query: $query");
    error_log("Parameters: " . print_r($product_ids, true));

    // Execute the query
    $stmt->execute($product_ids);

    // Fetch products as an associative array [id => row]
    $wishlistProducts = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $wishlistProducts[$row['id']] = $row;
    }
} catch (PDOException $e) {
    // Log the error and display a user-friendly message
    error_log("Database error: " . $e->getMessage());
    echo "<p>An error occurred while fetching wishlist items. Please try again later.</p>";
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
                $originalPrice = $product['price'];
                $discountPercentage = $product['discount'] ?? 0;
                $discountedPrice = $originalPrice - ($originalPrice * ($discountPercentage / 100));
            ?>
                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="card h-100">
                        <!-- Product Image -->
                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <div class="card-body d-flex flex-column justify-content-between">
                            <!-- Product Name -->
                            <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                            
                            <!-- Price -->
                            <p class="card-text text-danger">
                                <?php
                                echo '<span style="color: red; font-weight: bold;">KSH ' . number_format($discountedPrice, 2) . '</span>';
                                if ($discountPercentage > 0) {
                                    echo ' <del style="font-size: 0.8em; color: gray;">KSH ' . number_format($originalPrice, 2) . '</del>';
                                }
                                ?>
                            </p>
                            
                            <!-- Action Buttons -->
                            <div class="d-flex justify-content-between align-items-center mt-auto">
                                <form method="POST" action="remove_from_wishlist.php" class="m-0">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash me-1"></i> Remove
                                    </button>
                                </form>
                                <a href="add_to_cart.php?product_id=<?php echo $product['id']; ?>" class="btn btn-primary btn-sm">
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