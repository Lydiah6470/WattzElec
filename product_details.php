<?php
session_start();
include 'includes/header.php';
include 'includes/db.php';

// Handle flash messages
$flash_message = null;
if (isset($_SESSION['flash_message'])) {
    $flash_message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}


// Get product ID from URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Redirect to products page if no ID
if ($product_id === 0) {
    header('Location: products.php');
    exit;
}



// Fetch product details from the database
$query = "
    SELECT p.*, c.name AS category_name, s.name AS subcategory_name,
           CASE 
               WHEN p.discount > 0 THEN p.price - (p.price * p.discount / 100)
               ELSE p.price
           END as final_price
    FROM products p
    JOIN subcategories s ON p.subcategory_id = s.subcategory_id
    JOIN category c ON s.category_id = c.category_id
    WHERE p.product_id = ?
";

$stmt = $conn->prepare($query);
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    $_SESSION['error_message'] = 'Product not found.';
    header('Location: products.php');
    exit;
}

// Price information is already calculated in the query as final_price

// Fetch related products (from the same subcategory)
$relatedProductsQuery = "
    SELECT p.*, 
           CASE 
               WHEN p.discount > 0 THEN p.price - (p.price * p.discount / 100)
               ELSE p.price
           END as final_price
    FROM products p
    WHERE p.subcategory_id = ? AND p.product_id != ?
    LIMIT 4
";

$relatedStmt = $conn->prepare($relatedProductsQuery);
$relatedStmt->execute([$product['subcategory_id'], $product_id]);
$relatedProducts = $relatedStmt->fetchAll();

// Fetch reviews for the product
$reviewsQuery = "
    SELECT r.*, u.username
    FROM reviews r
    LEFT JOIN users u ON r.user_id = u.user_id
    WHERE r.product_id = ?
    ORDER BY r.review_date DESC
";

$reviewsStmt = $conn->prepare($reviewsQuery);
$reviewsStmt->execute([$product_id]);
$reviews = $reviewsStmt->fetchAll();
?>

<div class="container mt-4">
    <?php
    // Get category and subcategory info
    $categoryStmt = $conn->prepare("SELECT c.* FROM category c JOIN subcategories s ON c.category_id = s.category_id WHERE s.subcategory_id = ?");
    $categoryStmt->execute([$product['subcategory_id']]);
    $category = $categoryStmt->fetch(PDO::FETCH_ASSOC);
    ?>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <?php if ($category): ?>
            <li class="breadcrumb-item"><a href="category.php?id=<?php echo htmlspecialchars($category['category_id']); ?>"><?php echo htmlspecialchars($category['name']); ?></a></li>
            <?php endif; ?>
            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($product['name']); ?></li>
        </ol>
    </nav>
    <div class="row mt-4">
        <!-- Product Images -->
        <div class="col-md-6">
            <div class="product-images">
                <img src="<?php echo !empty($product['image_1']) ? htmlspecialchars($product['image_1']) : 'uploads/default-product.jpg'; ?>" 
                     class="main-image" 
                     id="main-image" 
                     alt="<?php echo htmlspecialchars($product['name']); ?>">
                <div class="thumbnail-container">
                    <?php if (!empty($product['image_1'])): ?>
                        <img src="<?php echo htmlspecialchars($product['image_1']); ?>" 
                             class="thumbnail active" 
                             onclick="changeImage(this.src)" 
                             alt="Product view 1">
                    <?php endif; ?>
                    <?php if (!empty($product['image_2'])): ?>
                        <img src="<?php echo htmlspecialchars($product['image_2']); ?>" 
                             class="thumbnail" 
                             onclick="changeImage(this.src)" 
                             alt="Product view 2">
                    <?php endif; ?>
                    <?php if (!empty($product['image_3'])): ?>
                        <img src="<?php echo htmlspecialchars($product['image_3']); ?>" 
                             class="thumbnail" 
                             onclick="changeImage(this.src)" 
                             alt="Product view 3">
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Product Details -->
        <div class="col-md-6">
            <h1><?php echo htmlspecialchars($product['name']); ?></h1>
            <span class="category-badge"><?php echo htmlspecialchars($product['category_name']); ?> » <?php echo htmlspecialchars($product['subcategory_name']); ?></span>
            <div class="mb-2">
                <span class="badge bg-secondary me-2">Category: <?php echo htmlspecialchars($product['category_name']); ?></span>
                <span class="badge bg-info">Subcategory: <?php echo htmlspecialchars($product['subcategory_name']); ?></span>
            </div>

            <!-- Price Section -->
            <?php // In add_to_cart.php and add_to_wishlist.php, set:
            // $_SESSION['flash_message'] = 'Successfully added to cart!';
            // and redirect back to product_details.php?id=... ?>
            <?php if ($flash_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert" id="flash-alert">
                    <?php echo htmlspecialchars($flash_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <div class="price-section">
                <?php if ($product['discount'] > 0): ?>
                    <div class="original-price">KSH <?php echo number_format($product['price'], 2); ?></div>
                    <div class="final-price">
                        KSH <?php echo number_format($product['price'] * (1 - $product['discount']/100), 2); ?>
                        <span class="discount-badge">-<?php echo $product['discount']; ?>% OFF</span>
                    </div>
                <?php else: ?>
                    <div class="final-price">KSH <?php echo number_format($product['price'], 2); ?></div>
                <?php endif; ?>
            </div>

            <!-- Stock Status -->
            <p class="text-muted mt-2">
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
            <?php if ($product['stock_quantity'] > 0): ?>
                <div class="action-buttons">
                    <div class="d-flex align-items-center">
                        <input type="number" id="quantity" value="1" min="1" 
                               max="<?php echo $product['stock_quantity']; ?>" 
                               class="quantity-input me-3">
                        <a href="add_to_cart.php?product_id=<?php echo $product['product_id']; ?>" 
                           class="btn btn-primary me-3">
                            <i class="fas fa-shopping-cart"></i> Add to Cart
                        </a>
                        <a href="add_to_wishlist.php?product_id=<?php echo $product['product_id']; ?>" 
                           class="btn btn-outline-primary">
                            <i class="fas fa-heart"></i> Add to Wishlist
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="action-buttons">
                    <button class="btn btn-secondary" disabled>
                        <i class="fas fa-shopping-cart"></i> Out of Stock
                    </button>
                    <a href="add_to_wishlist.php?product_id=<?php echo $product['product_id']; ?>" 
                       class="btn btn-outline-primary">
                        <i class="fas fa-heart"></i> Add to Wishlist
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Product Description -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h4>Description</h4>
                    <?php
                    $description = htmlspecialchars($product['description']);
                    $shortLength = 300; // Characters to show initially
                    
                    if (strlen($description) > $shortLength):
                        $shortDesc = substr($description, 0, $shortLength);
                        $remainingDesc = substr($description, $shortLength);
                    ?>
                        <p id="short-description"><?php echo nl2br($shortDesc); ?>... 
                            <a href="#" id="read-more-btn" class="read-more-btn">Read More</a>
                        </p>
                        <p id="full-description" style="display: none;">
                            <?php echo nl2br($description); ?>
                            <a href="#" id="read-less-btn" class="read-more-btn">Read Less</a>
                        </p>
                    <?php else: ?>
                        <p><?php echo nl2br($description); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Reviews -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Customer Reviews</h4>
                </div>
                <div class="card-body">
                    <?php if (count($reviews) > 0): ?>
                        <?php foreach ($reviews as $review): ?>
                            <div class="review-item mb-4 pb-3 border-bottom">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="review-stars mb-2">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <strong class="d-block"><?php echo htmlspecialchars($review['username']); ?></strong>
                                    </div>
                                    <small class="text-muted"><?php echo date('M j, Y', strtotime($review['review_date'])); ?></small>
                                </div>
                                <p class="mt-2 mb-0"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">No reviews yet. Be the first to review this product!</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Related Products -->
    <div class="related-products">
        <h2>Related Products</h2>
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="product-container">
                    <div class="row">
                        <?php if (count($relatedProducts) > 0): ?>
                            <?php foreach ($relatedProducts as $relatedProduct): ?>
                                <div class="col-md-3 col-sm-6 mb-4">
                                    <div class="card h-100">
                                        <img src="<?= htmlspecialchars($relatedProduct['image_1'] ?? 'images/placeholder.php') ?>" 
                                             class="card-img-top" 
                                             alt="<?= htmlspecialchars($relatedProduct['name']) ?>">
                                        <div class="card-body d-flex flex-column justify-content-between">
                                            <h5 class="card-title"><?= htmlspecialchars($relatedProduct['name']) ?></h5>
                                            <p class="card-text text-danger">
                                                <?php if ($relatedProduct['discount'] > 0): ?>
                                                    <del style="font-size: 0.8em; color: gray;">KSH <?= number_format($relatedProduct['price'], 2) ?></del>
                                                    <span class="badge bg-danger">-<?= $relatedProduct['discount'] ?>%</span><br>
                                                <?php endif; ?>
                                                <span style="color: red; font-weight: bold;">KSH <?= number_format($relatedProduct['final_price'], 2) ?></span>
                                            </p>
                                            <a href="product_details.php?id=<?php echo $relatedProduct['product_id']; ?>" class="btn btn-primary w-100 mt-auto">View Details</a>
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
        </div>
    </div>
</div>

<style>
    :root {
        --primary-color: #4361ee;
        --secondary-color: #3f37c9;
        --accent-color: #4895ef;
        --text-color: #2d3436;
        --light-bg: #f8f9fa;
        --dark-bg: #212529;
    }

    body {
        background-color: var(--light-bg);
        color: var(--text-color);
    }

    .product-container {
        background: white;
        border-radius: 1rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        padding: 2rem;
        margin: 2rem 0;
    }

    .product-images {
        position: relative;
        margin-bottom: 2rem;
    }

    .main-image {
        width: 100%;
        height: 400px;
        object-fit: cover;
        border-radius: 0.5rem;
        margin-bottom: 1rem;
    }

    .thumbnail-container {
        display: flex;
        gap: 1rem;
        margin-top: 1rem;
    }

    .thumbnail {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 0.5rem;
        cursor: pointer;
        border: 2px solid transparent;
        transition: all 0.3s ease;
    }

    .thumbnail:hover {
        border-color: var(--primary-color);
        transform: scale(1.05);
    }

    .product-info h1 {
        color: var(--dark-bg);
        margin-bottom: 1rem;
        font-size: 2.5rem;
    }

    .category-badge {
        background: var(--accent-color);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 2rem;
        font-size: 0.9rem;
        margin-bottom: 1rem;
        display: inline-block;
    }

    .price-section {
        background: var(--light-bg);
        padding: 1.5rem;
        border-radius: 0.5rem;
        margin: 1.5rem 0;
    }

    .original-price {
        text-decoration: line-through;
        color: #666;
        font-size: 1.2rem;
    }

    .final-price {
        font-size: 2.5rem;
        color: var(--primary-color);
        font-weight: bold;
    }

    .discount-badge {
        background: #dc3545;
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 2rem;
        margin-left: 1rem;
        font-size: 1.1rem;
    }

    .stock-status {
        margin: 1rem 0;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        font-weight: bold;
        display: inline-block;
    }

    .in-stock {
        background: #d4edda;
        color: #155724;
    }

    .low-stock {
        background: #fff3cd;
        color: #856404;
    }

    .out-of-stock {
        background: #f8d7da;
        color: #721c24;
    }

    .description {
        background: white;
        padding: 1.5rem;
        border-radius: 0.5rem;
        margin: 1.5rem 0;
        line-height: 1.8;
    }

    .action-buttons {
        display: flex;
        gap: 1rem;
        margin-top: 2rem;
    }

    .action-buttons .btn {
        padding: 0.8rem 2rem;
        font-size: 1.1rem;
        border-radius: 0.5rem;
        transition: all 0.3s ease;
    }

    .action-buttons .btn:hover {
        transform: translateY(-2px);
    }

    .quantity-input {
        width: 100px;
        text-align: center;
        padding: 0.5rem;
        border: 1px solid #ddd;
        border-radius: 0.5rem;
        margin-right: 1rem;
    }

    .related-products {
        margin-top: 3rem;
    }

    .related-products h2 {
        margin-bottom: 2rem;
        color: var(--dark-bg);
        text-align: center;
    }

    .related-product-card {
        background: white;
        border-radius: 0.5rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
        height: 100%;
    }

    .related-product-card:hover {
        transform: translateY(-5px);
    }

    .related-product-image {
        width: 100%;
        height: 200px;
        object-fit: cover;
        border-radius: 0.5rem 0.5rem 0 0;
    }

    .related-product-info {
        padding: 1rem;
    }

    .related-product-price {
        color: var(--primary-color);
        font-weight: bold;
        font-size: 1.2rem;
        margin: 0.5rem 0;
    }
    .card, .card-body, .alert, .btn, .badge, .carousel, .carousel-inner, .carousel-item, .img-fluid, .form-control {
        border-radius: 0.75rem !important;
    }
    .btn-primary, .btn-outline-danger {
        font-weight: 600;
        letter-spacing: 0.02em;
    }
    .card-img-top {
        height: 220px;
        object-fit: cover;
        border-top-left-radius: 0.75rem;
        border-top-right-radius: 0.75rem;
    }
    .card {
        box-shadow: 0 2px 12px rgba(0,0,0,0.07);
        transition: box-shadow 0.2s;
    }
    .card:hover {
        box-shadow: 0 6px 24px rgba(0,0,0,0.13);
    }
    .badge {
        font-size: 0.95em;
        padding: 0.5em 0.8em;
    }
    .alert-success {
        font-size: 1.1em;
        margin-bottom: 1em;
    }
    .carousel-item img {
        max-height: 350px;
        object-fit: contain;
        background: #f9f9f9;
    }
    #desc-short { font-size: 1.1em; }
    #read-more-link { color: #007bff; cursor: pointer; margin-left: 0.5em; }
    #read-more-link:hover { text-decoration: underline; }
    .review-card .card-title { font-weight: 700; }
    .review-card .text-warning { font-size: 1.1em; }
    /* Read More Button */
    .read-more-btn {
        color: var(--primary-color);
        text-decoration: none;
        font-weight: 500;
        display: inline-block;
        margin-left: 5px;
        transition: all 0.3s ease;
    }

    .read-more-btn:hover {
        color: var(--secondary-color);
        text-decoration: underline;
    }

    #short-description,
    #full-description {
        line-height: 1.8;
        transition: all 0.3s ease;
    }

    #full-description {
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
</style>

<?php include 'includes/footer.php'; ?>