<?php
include 'includes/db.php';
include 'includes/header.php';
include 'includes/functions.php';

// Fetch data from the database
$categories = getCategoriesFromDatabase();
$featuredProducts = getFeaturedProductsFromDatabase();
$otherProducts = getOtherProductsFromDatabase();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wattz Electronics</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --success-color: #2ecc71;
            --text-color: #2d3436;
            --light-bg: #f8f9fa;
            --dark-bg: #212529;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-color);
            background-color: var(--light-bg);
        }

        /* Hero Section */
        .hero-section {
            margin-bottom: 3rem;
            overflow: hidden;
        }

        .carousel-item {
            height: 500px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }

        .carousel-item img {
            height: 100%;
            object-fit: cover;
            filter: brightness(0.7);
        }

        .carousel-caption {
            top: 50%;
            transform: translateY(-50%);
            bottom: auto;
            padding: 0 2rem;
        }

        .carousel-caption h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        .carousel-caption .lead {
            font-size: 1.5rem;
            margin-bottom: 2rem;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
        }

        .carousel-caption .btn {
            font-size: 1.2rem;
            padding: 0.75rem 2rem;
            text-shadow: none;
            transition: transform 0.3s ease;
        }

        .carousel-caption .btn:hover {
            transform: translateY(-3px);
        }

        @media (max-width: 768px) {
            .carousel-item {
                height: 400px;
            }

            .carousel-caption h1 {
                font-size: 2.5rem;
            }

            .carousel-caption .lead {
                font-size: 1.2rem;
            }
        }

        /* Categories */
        .category-list {
            list-style-type: none;
            padding: 0;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }

        .category-item {
            text-align: center;
            padding: 2rem;
            background: var(--light-bg);
            border-radius: 1rem;
            transition: transform 0.3s ease;
        }

        .category-item:hover {
            transform: translateY(-5px);
        }

        .category-item i {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .category-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 1rem;
        }

        .category-description {
            color: #666;
            margin: 0.5rem 0;
            font-size: 0.9rem;
        }

        .no-categories {
            grid-column: 1 / -1;
            padding: 3rem;
            text-align: center;
            background: #f8f9fa;
            border-radius: 1rem;
        }

        .no-categories i {
            font-size: 3rem;
            color: #dc3545;
            margin-bottom: 1rem;
        }

        /* Featured Products */
        .featured-products {
            padding: 3rem 0;
            background-color: white;
            border-radius: 1rem;
            margin: 2rem 0;
        }

        /* Products grid */
        .row {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }

        .col-md-3, .col-md-4, .col-sm-6 {
            width: 100%;
            padding: 0;
        }

        .product-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            margin-bottom: 0;
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .product-card:hover {
            transform: translateY(-5px);
        }

        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .product-info {
            padding: 1.5rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .product-info h3 {
            margin-bottom: 0.5rem;
        }

        .product-price {
            margin-top: auto;
            padding-top: 1rem;
        }

        .btn-primary {
            margin-top: 1rem;
        }

        .category-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            background-color: var(--accent-color);
            color: white;
            border-radius: 1rem;
            font-size: 0.8rem;
            margin-bottom: 0.5rem;
        }

        .product-price {
            color: var(--primary-color);
            font-size: 1.25rem;
            font-weight: 600;
        }

        .original-price {
            color: var(--text-secondary);
            text-decoration: line-through;
            font-size: 1rem;
            margin-right: 0.5rem;
        }

        .final-price {
            color: var(--success-color);
        }

        /* Other Products */
        .other-products {
            padding: 4rem 0;
            background-color: var(--light-bg);
        }

        /* Promotional Banners */
        .promotional-banners {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
            margin: 4rem 0;
        }

        .banner {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .banner:hover {
            transform: translateY(-5px);
        }

        .banner i {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        /* Buttons */
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            transform: translateY(-2px);
        }

        /* Section Headers */
        .section-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .section-header h2 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .section-header p {
            color: #666;
            font-size: 1.1rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero-content h1 {
                font-size: 2rem;
            }

            .category-item {
                margin-bottom: 1rem;
            }

            .promotional-banners {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Hero Section with Slider -->
    <section class="hero-section">
        <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <img src="images/slide1.jpg" class="d-block w-100" alt="Welcome to Wattz Electronics">
                    <div class="carousel-caption">
                        <h1>Welcome to Wattz Electronics</h1>
                        <p class="lead">Your One-Stop Shop for Quality Electronics</p>
                        <a href="products.php" class="btn btn-light btn-lg">Shop Now</a>
                    </div>
                </div>
                <div class="carousel-item">
                    <img src="images/slide2.jpg" class="d-block w-100" alt="Quality Electronics">
                    <div class="carousel-caption">
                        <h1>Quality Electronics</h1>
                        <p class="lead">Discover Our Wide Range of Products</p>
                        <a href="products.php" class="btn btn-light btn-lg">Explore Now</a>
                    </div>
                </div>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>
    </section>

    <!-- Categories Section -->
    <section class="container">
        <div class="section-header">
            <h2>Browse Categories</h2>
            <p>Find what you need in our extensive collection</p>
        </div>
        <ul class="category-list">
            <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $category): ?>
                <li class="category-item">
                    <?php if (!empty($category['image'])): ?>
                        <img src="<?php echo htmlspecialchars($category['image']); ?>" alt="<?php echo htmlspecialchars($category['name']); ?>" class="category-image">
                    <?php else: ?>
                        <i class="fas fa-plug"></i>
                    <?php endif; ?>
                    <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                    <?php if (!empty($category['description'])): ?>
                        <p class="category-description"><?php echo htmlspecialchars($category['description']); ?></p>
                    <?php endif; ?>
                    <a href="category.php?id=<?php echo $category['category_id']; ?>" class="btn btn-primary">View Products</a>
                </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li class="category-item no-categories">
                    <i class="fas fa-exclamation-circle"></i>
                    <h3>No Categories Found</h3>
                    <p class="text-muted">Please check back later for our product categories.</p>
                </li>
            <?php endif; ?>
        </ul>
    </section>

    <!-- Featured Products Section -->
    <section class="featured-products">
        <div class="container">
            <div class="section-header">
                <h2>Featured Products</h2>
                <p>Check out our top picks for you</p>
            </div>
            <div class="row">
                <?php foreach ($featuredProducts as $product): ?>
                <div class="col-md-4 col-sm-6">
                    <div class="product-card">
                        <img src="<?php echo !empty($product['image_1']) ? htmlspecialchars($product['image_1']) : 'uploads/default-product.jpg'; ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                             class="product-image">

                        <div class="product-info">
                            <span class="category-badge"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></span>
                            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                            <?php if ($product['discount'] > 0): ?>
                                <p class="product-price">
                                    <span class="original-price">KSH <?php echo number_format($product['price'], 2); ?></span>
                                    <span class="final-price">KSH <?php echo number_format($product['final_price'], 2); ?></span>
                                    <span class="discount-badge">-<?php echo number_format($product['discount']); ?>%</span>
                                </p>
                            <?php else: ?>
                                <p class="product-price">KSH <?php echo number_format($product['price'], 2); ?></p>
                            <?php endif; ?>
                            <a href="product_details.php?id=<?php echo $product['product_id']; ?>" class="btn btn-primary">View Details</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Promotional Banners -->
    <section class="container">
        <div class="promotional-banners">
            <div class="banner">
                <i class="fas fa-shield-alt"></i>
                <h3>Secure Payment</h3>
                <p>100% secure payment</p>
            </div>
            <div class="banner">
                <i class="fas fa-headset"></i>
                <h3>24/7 Support</h3>
                <p>Dedicated support</p>
            </div>
            <div class="banner">
                <i class="fas fa-undo"></i>
                <h3>Easy Returns</h3>
                <p>14-day return policy</p>
            </div>
        </div>
    </section>

    <!-- Other Products Section -->
    <section class="other-products">
        <div class="container">
            <div class="section-header">
                <h2>More Products</h2>
                <p>Discover our other amazing products</p>
            </div>
            <div class="row">
                <?php foreach ($otherProducts as $product): ?>
                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="product-card">
                        <img src="<?php echo !empty($product['image_1']) ? htmlspecialchars($product['image_1']) : 'uploads/default-product.jpg'; ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                             class="product-image">
                        <div class="product-info">
                            <span class="category-badge"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></span>
                            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                            <?php if ($product['discount'] > 0): ?>
                                <p class="product-price">
                                    <span class="original-price">KSH <?php echo number_format($product['price'], 2); ?></span>
                                    <span class="final-price">KSH <?php echo number_format($product['final_price'], 2); ?></span>
                                    <span class="discount-badge">-<?php echo number_format($product['discount']); ?>%</span>
                                </p>
                            <?php else: ?>
                                <p class="product-price">KSH <?php echo number_format($product['price'], 2); ?></p>
                            <?php endif; ?>
                            <a href="product_details.php?id=<?php echo $product['product_id']; ?>" class="btn btn-primary">View Details</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center mt-4">
                <a href="products.php" class="btn btn-lg btn-outline-primary">View All Products</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center p-3 mt-5">
        <p>&copy; 2023 Wattz Electronics. All rights reserved.</p>
    </footer>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>