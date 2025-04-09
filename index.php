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
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            padding: 4rem 0;
            margin-bottom: 3rem;
            color: white;
            border-radius: 0 0 2rem 2rem;
        }

        .hero-content {
            text-align: center;
            max-width: 800px;
            margin: 0 auto;
        }

        .hero-content h1 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
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
            background: white;
            padding: 1.5rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            text-align: center;
        }

        .category-item:hover {
            transform: translateY(-5px);
        }

        .category-item i {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        /* Featured Products */
        .featured-products {
            padding: 3rem 0;
            background-color: white;
            border-radius: 1rem;
            margin: 2rem 0;
        }

        .product-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            margin-bottom: 2rem;
            overflow: hidden;
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
        }

        .product-price {
            color: var(--primary-color);
            font-size: 1.25rem;
            font-weight: 600;
        }

        /* Promotional Banners */
        .promotional-banners {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin: 3rem 0;
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
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content">
                <h1>Welcome to Wattz Electronics</h1>
                <p class="lead">Your One-Stop Shop for Quality Electronics</p>
                <a href="products.php" class="btn btn-light btn-lg">Shop Now</a>
            </div>
        </div>
    </section>

    <!-- Categories Section -->
    <section class="container">
        <div class="section-header">
            <h2>Browse Categories</h2>
            <p>Find what you need in our extensive collection</p>
        </div>
        <ul class="category-list">
            <?php foreach ($categories as $category): ?>
            <li class="category-item">
                <i class="fas fa-plug"></i>
                <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                <a href="category.php?id=<?php echo $category['id']; ?>" class="btn btn-primary">View Products</a>
            </li>
            <?php endforeach; ?>
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
                        <img src="<?php echo !empty($product['image_url']) ? htmlspecialchars($product['image_url']) : 'uploads/default-product.jpg'; ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                             class="product-image">
                        <div class="product-info">
                            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="product-price">KSH <?php echo number_format($product['price'], 2); ?></p>
                            <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary">View Details</a>
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
                <i class="fas fa-truck"></i>
                <h3>Free Delivery</h3>
                <p>On orders above KSH 5000</p>
            </div>
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
        </div>
    </section>

    <!-- Other Products Section -->
    <section class="container mt-5">
        <h2>Other Products</h2>
        <div class="row">
            <?php foreach ($otherProducts as $product): ?>
            <div class="col-md-3 mb-4">
                <div class="card h-100">
                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" class="card-img-top" alt="Product Image">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                        <p class="price">KSH. <?php echo htmlspecialchars($product['price']); ?></p>
                        <a href="add_to_cart.php?product_id=<?php echo $product['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-shopping-cart me-2"></i>Add to Cart
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
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