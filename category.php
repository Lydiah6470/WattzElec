<?php
include 'includes/db.php';
include 'includes/header.php';

// Get category ID from URL
$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get sorting parameters
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'name_asc';
$price_min = isset($_GET['price_min']) ? (float)$_GET['price_min'] : 0;
$price_max = isset($_GET['price_max']) ? (float)$_GET['price_max'] : PHP_FLOAT_MAX;

// Fetch category details
$categoryQuery = "SELECT * FROM category WHERE category_id = :category_id";
$categoryStmt = $conn->prepare($categoryQuery);
$categoryStmt->execute(['category_id' => $category_id]);
$category = $categoryStmt->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    header('Location: index.php');
    exit;
}

// Build the products query with filters
$query = "SELECT p.* FROM products p 
          JOIN subcategories s ON p.subcategory_id = s.subcategory_id
          WHERE s.category_id = :category_id 
          AND p.price >= :price_min 
          AND p.price <= :price_max";

// Add sorting
switch ($sort) {
    case 'price_asc':
        $query .= " ORDER BY p.price ASC";
        break;
    case 'price_desc':
        $query .= " ORDER BY p.price DESC";
        break;
    case 'name_desc':
        $query .= " ORDER BY p.name DESC";
        break;
    default: // name_asc
        $query .= " ORDER BY p.name ASC";
}

$stmt = $conn->prepare($query);
$stmt->execute([
    'category_id' => $category_id,
    'price_min' => $price_min,
    'price_max' => $price_max
]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get price range for this category
$priceQuery = "SELECT MIN(p.price) as min_price, MAX(p.price) as max_price 
               FROM products p
               JOIN subcategories s ON p.subcategory_id = s.subcategory_id
               WHERE s.category_id = :category_id";
$priceStmt = $conn->prepare($priceQuery);
$priceStmt->execute(['category_id' => $category_id]);
$priceRange = $priceStmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($category['name']); ?> - WattzElec</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .category-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 1rem 1rem;
        }

        .filter-sidebar {
            background: white;
            padding: 1.5rem;
            border-radius: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: sticky;
            top: 1rem;
        }

        .product-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
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
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--primary-color);
            margin: 0.5rem 0;
        }

        .original-price {
            text-decoration: line-through;
            color: #999;
            font-size: 1rem;
            font-weight: normal;
        }

        .final-price {
            color: var(--primary-color);
            font-size: 1.3rem;
            font-weight: bold;
        }

        .discount-badge {
            background-color: #e74c3c;
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 3px;
            font-size: 0.9rem;
            margin-left: 0.5rem;
            display: inline-block;
        }

        .stock-status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }

        .in-stock {
            background-color: #d4edda;
            color: #155724;
        }

        .low-stock {
            background-color: #fff3cd;
            color: #856404;
        }

        .out-of-stock {
            background-color: #f8d7da;
            color: #721c24;
        }

        .sort-select {
            padding: 0.5rem;
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            background-color: white;
        }

        .price-range {
            margin: 1rem 0;
        }

        .price-inputs {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .price-input {
            width: 100px;
            padding: 0.5rem;
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
        }

        .btn-filter {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn-filter:hover {
            background-color: var(--secondary-color);
        }

        @media (max-width: 768px) {
            .filter-sidebar {
                position: static;
                margin-bottom: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Category Header -->
    <div class="category-header">
        <div class="container">
            <h1 class="display-4"><?php echo htmlspecialchars($category['name']); ?></h1>
            <p class="lead"><?php echo htmlspecialchars($category['description'] ?? ''); ?></p>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <!-- Filter Sidebar -->
            <div class="col-md-3">
                <div class="filter-sidebar">
                    <h3>Filters</h3>
                    <form action="" method="GET">
                        <input type="hidden" name="id" value="<?php echo $category_id; ?>">
                        
                        <div class="mb-4">
                            <label class="form-label">Sort By</label>
                            <select name="sort" class="form-select sort-select" onchange="this.form.submit()">
                                <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Name (A-Z)</option>
                                <option value="name_desc" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>Name (Z-A)</option>
                                <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Price (Low to High)</option>
                                <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Price (High to Low)</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Price Range</label>
                            <div class="price-inputs">
                                <input type="number" name="price_min" class="price-input" 
                                       value="<?php echo $price_min; ?>" 
                                       min="<?php echo floor($priceRange['min_price']); ?>" 
                                       max="<?php echo ceil($priceRange['max_price']); ?>" 
                                       placeholder="Min">
                                <span>to</span>
                                <input type="number" name="price_max" class="price-input" 
                                       value="<?php echo $price_max < PHP_FLOAT_MAX ? $price_max : ''; ?>" 
                                       min="<?php echo floor($priceRange['min_price']); ?>" 
                                       max="<?php echo ceil($priceRange['max_price']); ?>" 
                                       placeholder="Max">
                            </div>
                            <button type="submit" class="btn btn-filter w-100 mt-2">Apply Filters</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Products Grid -->
            <div class="col-md-9">
                <div class="row">
                    <?php if (empty($products)): ?>
                    <div class="col-12">
                        <div class="alert alert-info">
                            No products found in this category.
                        </div>
                    </div>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                        <div class="col-md-4 col-sm-6">
                            <div class="product-card">
                                <img src="<?php echo !empty($product['image_1']) ? htmlspecialchars($product['image_1']) : 'uploads/default-product.jpg'; ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                     class="product-image">
                                <div class="product-info">
                                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                    <?php if ($product['discount'] > 0): ?>
                                        <p class="product-price">
                                            <span class="original-price">KSH <?php echo number_format($product['price'], 2); ?></span>
                                            <span class="discount-badge">-<?php echo $product['discount']; ?>%</span>
                                            <br>
                                            <span class="final-price">KSH <?php echo number_format($product['price'] * (1 - $product['discount']/100), 2); ?></span>
                                        </p>
                                    <?php else: ?>
                                        <p class="product-price">KSH <?php echo number_format($product['price'], 2); ?></p>
                                    <?php endif; ?>
                                    
                                    <?php
                                    $stockClass = '';
                                    $stockText = '';
                                    if ($product['stock_quantity'] > 10) {
                                        $stockClass = 'in-stock';
                                        $stockText = 'In Stock';
                                    } elseif ($product['stock_quantity'] > 0) {
                                        $stockClass = 'low-stock';
                                        $stockText = 'Low Stock';
                                    } else {
                                        $stockClass = 'out-of-stock';
                                        $stockText = 'Out of Stock';
                                    }
                                    ?>
                                    <div class="stock-status <?php echo $stockClass; ?>">
                                        <?php echo $stockText; ?>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <a href="product_details.php?id=<?php echo $product['product_id']; ?>" class="btn btn-primary">
                                            View Details
                                        </a>
                                        <?php if ($product['stock_quantity'] > 0): ?>
                                        <a href="add_to_cart.php?product_id=<?php echo $product['product_id']; ?>" class="btn btn-outline-primary">
                                            <i class="fas fa-shopping-cart"></i> Add to Cart
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
