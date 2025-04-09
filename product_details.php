<?php
session_start();
include 'includes/header.php';
include 'includes/db.php';

// Get product ID from URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($product_id === 0) {
    echo "<p>Invalid product ID.</p>";
    exit;
}

// Fetch product details from the database
$query = "
    SELECT p.id, p.name, p.image_url, p.image_url_2, p.image_url_3, p.price, p.discount, p.stock, p.description, p.subcategory_id, c.name AS category_name, s.name AS subcategory_name
    FROM products p
    JOIN subcategories s ON p.subcategory_id = s.id
    JOIN category c ON s.category_id = c.id
    WHERE p.id = ?
";

$stmt = $conn->prepare($query);
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo "<p>Product not found.</p>";
    exit;
}

// Calculate discounted price
$originalPrice = $product['price'];
$discountPercentage = $product['discount'] ?? 0;
$discountedPrice = $originalPrice - ($originalPrice * ($discountPercentage / 100));

// Fetch related products (from the same subcategory)
$relatedProductsQuery = "
    SELECT id, name, image_url, price, discount
    FROM products
    WHERE subcategory_id = ? AND id != ?
    LIMIT 4
";

$relatedStmt = $conn->prepare($relatedProductsQuery);
$relatedStmt->execute([$product['subcategory_id'], $product_id]);
$relatedProducts = $relatedStmt->fetchAll();

// Fetch reviews for the product
$reviewsQuery = "
    SELECT user_name, rating, comment, created_at
    FROM reviews
    WHERE product_id = ?
    ORDER BY created_at DESC
";

$reviewsStmt = $conn->prepare($reviewsQuery);
$reviewsStmt->execute([$product_id]);
$reviews = $reviewsStmt->fetchAll();
?>

<div class="container-fluid">
    <div class="row mt-4">
        <!-- Product Images -->
        <div class="col-md-6">
            <div id="productCarousel" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <?php
                    // Display the first image as the active slide
                    if (!empty($product['image_url'])) {
                        echo '<div class="carousel-item active">';
                        echo '<img src="' . htmlspecialchars($product['image_url']) . '" class="d-block w-100 img-fluid rounded" alt="' . htmlspecialchars($product['name']) . '">';
                        echo '</div>';
                    }

                    // Display additional images
                    if (!empty($product['image_url_2'])) {
                        echo '<div class="carousel-item">';
                        echo '<img src="' . htmlspecialchars($product['image_url_2']) . '" class="d-block w-100 img-fluid rounded" alt="' . htmlspecialchars($product['name']) . '">';
                        echo '</div>';
                    }

                    if (!empty($product['image_url_3'])) {
                        echo '<div class="carousel-item">';
                        echo '<img src="' . htmlspecialchars($product['image_url_3']) . '" class="d-block w-100 img-fluid rounded" alt="' . htmlspecialchars($product['name']) . '">';
                        echo '</div>';
                    }
                    ?>
                </div>
                <!-- Carousel Controls -->
                <button class="carousel-control-prev" type="button" data-bs-target="#productCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#productCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
            </div>
        </div>

        <!-- Product Details -->
        <div class="col-md-6">
            <h2 class="mb-3"><?php echo htmlspecialchars($product['name']); ?></h2>

            <!-- Price Section -->
            <div class="mb-3">
                <?php
                // Display discounted price first
                echo '<span style="color: red; font-weight: bold; font-size: 1.5rem;">KSH ' . number_format($discountedPrice, 2) . '</span>';
                
                // Display original price with strikethrough and smaller font size
                if ($discountPercentage > 0) {
                    echo ' <del style="font-size: 1rem; color: gray;">KSH ' . number_format($originalPrice, 2) . '</del>';
                }
                ?>

                <!-- Stock Status -->
                <p class="text-muted mt-2">
                    <?php
                    $stock = $product['stock'] ?? 0;
                    echo $stock > 0 ? "In Stock ({$stock} left)" : "Out of Stock";
                    ?>
                </p>
            </div>

            <!-- Action Buttons -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="add_to_wishlist.php?product_id=<?php echo $product['id']; ?>" class="btn btn-danger">
                        <i class="fas fa-heart me-2"></i>Add to Wishlist
                    </a>
                    <a href="add_to_cart.php?product_id=<?php echo $product['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-shopping-cart me-2"></i>Add to Cart
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-danger"><i class="fas fa-heart me-2"></i>Login to Add to Wishlist</a>
                    <a href="login.php" class="btn btn-primary"><i class="fas fa-shopping-cart me-2"></i>Login to Add to Cart</a>
                <?php endif; ?>
            </div>

            <!-- Additional Information -->
            <div class="mt-4">
                <h4>Additional Information</h4>
                <ul class="list-group">
                    <li class="list-group-item"><strong>Category:</strong> <?php echo htmlspecialchars($product['category_name']); ?></li>
                    <li class="list-group-item"><strong>Subcategory:</strong> <?php echo htmlspecialchars($product['subcategory_name']); ?></li>
                    <li class="list-group-item"><strong>Stock:</strong> <?php echo htmlspecialchars($product['stock']); ?> units</li>
                    <li class="list-group-item"><strong>Discount:</strong> <?php echo $discountPercentage > 0 ? htmlspecialchars($discountPercentage) . '%' : 'No Discount'; ?></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Product Description -->
    <div class="row mt-4">
        <div class="col-md-12">
            <h4>Description</h4>
            <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
        </div>
    </div>

    <!-- Related Products -->
    <div class="row mt-4">
        <div class="col-md-12">
            <h4>Related Products</h4>
            <div class="row">
                <?php if (count($relatedProducts) > 0): ?>
                    <?php foreach ($relatedProducts as $relatedProduct): ?>
                        <div class="col-md-3 col-sm-6 mb-4">
                            <div class="card h-100">
                                <img src="<?php echo htmlspecialchars($relatedProduct['image_url']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($relatedProduct['name']); ?>">
                                <div class="card-body d-flex flex-column justify-content-between">
                                    <h5 class="card-title"><?php echo htmlspecialchars($relatedProduct['name']); ?></h5>
                                    <p class="card-text text-danger">
                                        <?php
                                        $originalPrice = $relatedProduct['price'];
                                        $discountPercentage = $relatedProduct['discount'] ?? 0;
                                        $discountedPrice = $originalPrice - ($originalPrice * ($discountPercentage / 100));
                                        echo '<span style="color: red; font-weight: bold;">KSH ' . number_format($discountedPrice, 2) . '</span>';
                                        if ($discountPercentage > 0) {
                                            echo ' <del style="font-size: 0.8em; color: gray;">KSH ' . number_format($originalPrice, 2) . '</del>';
                                        }
                                        ?>
                                    </p>
                                    <a href="product_details.php?id=<?php echo $relatedProduct['id']; ?>" class="btn btn-primary w-100 mt-auto">View Details</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No related products found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Reviews -->
    <div class="row mt-4">
        <div class="col-md-12">
            <h4>Customer Reviews</h4>
            <?php if (count($reviews) > 0): ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title">
                                <?php echo htmlspecialchars($review['user_name']); ?>
                                <span class="text-warning">
                                    <?php echo str_repeat('★', $review['rating']); ?>
                                    <?php echo str_repeat('☆', 5 - $review['rating']); ?>
                                </span>
                            </h5>
                            <p class="card-text"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                            <p class="card-text text-muted small"><?php echo date('M j, Y', strtotime($review['created_at'])); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No reviews available for this product.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>