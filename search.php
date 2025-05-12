<?php
include 'includes/header.php';

// Get search query
$query = isset($_GET['query']) ? trim($_GET['query']) : '';
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// Initialize variables for pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = 12;
$offset = ($page - 1) * $items_per_page;

// Base query parts
$select_base = "FROM products p 
        LEFT JOIN subcategories s ON p.subcategory_id = s.subcategory_id
        LEFT JOIN category c ON s.category_id = c.category_id
        WHERE p.name LIKE ? AND p.status = 'in_stock' AND p.stock_quantity > 0";

$params = ["%$query%"];

if ($category > 0) {
    $select_base .= " AND c.category_id = ?";
    $params[] = $category;
}

// Count total results
$count_sql = "SELECT COUNT(*) as total " . $select_base;
$stmt = $conn->prepare($count_sql);
$stmt->execute($params);
$total_results = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_results / $items_per_page);

// Get products for current page
$products_sql = "SELECT p.*, c.name as category_name, 
        CASE 
            WHEN p.discount > 0 THEN p.price * (1 - p.discount/100)
            ELSE p.price 
        END as final_price " . $select_base . " ORDER BY p.name ASC LIMIT ?, ?";

// Add pagination parameters
$all_params = array_merge($params, [$offset, $items_per_page]);
$stmt = $conn->prepare($products_sql);
$stmt->execute($all_params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all categories for the filter
$cat_stmt = $conn->query("SELECT * FROM category ORDER BY name");
$categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <h1>Search Results</h1>
    <p>Showing results for: "<?php echo htmlspecialchars($query); ?>"</p>

    <!-- Search filters -->
    <div class="row mb-4">
        <div class="col-md-6">
            <form action="search.php" method="GET" class="d-flex">
                <input type="text" name="query" value="<?php echo htmlspecialchars($query); ?>" class="form-control me-2" required>
                <select name="category" class="form-select me-2" style="width: auto;">
                    <option value="0">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['category_id']; ?>" <?php echo $category == $cat['category_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
        </div>
    </div>

    <?php if (empty($products)): ?>
        <div class="alert alert-info">
            No products found matching your search criteria.
        </div>
    <?php else: ?>
        <!-- Products grid -->
        <div class="row">
            <?php foreach ($products as $product): ?>
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

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Search results pages" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?query=<?php echo urlencode($query); ?>&category=<?php echo $category; ?>&page=<?php echo ($page-1); ?>">Previous</a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?query=<?php echo urlencode($query); ?>&category=<?php echo $category; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?query=<?php echo urlencode($query); ?>&category=<?php echo $category; ?>&page=<?php echo ($page+1); ?>">Next</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
