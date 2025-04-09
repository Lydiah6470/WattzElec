<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Get filter parameters
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : null;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'name_asc';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;

// Build the query
$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN category c ON p.category_id = c.id 
          WHERE 1=1";
$params = [];

if ($category_id) {
    $query .= " AND p.category_id = :category_id";
    $params[':category_id'] = $category_id;
}

if ($search) {
    $query .= " AND (p.name LIKE :search OR p.description LIKE :search)";
    $params[':search'] = "%$search%";
}

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
    default:
        $query .= " ORDER BY p.name ASC";
}

// Get total count for pagination
try {
    $count_stmt = $conn->prepare(str_replace("p.*, c.name as category_name", "COUNT(*) as count", $query));
    $count_stmt->execute($params);
    $total_products = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
} catch (PDOException $e) {
    $total_products = 0;
}

// Add pagination
$total_pages = ceil($total_products / $per_page);
$offset = ($page - 1) * $per_page;
$query .= " LIMIT :limit OFFSET :offset";
$params[':limit'] = $per_page;
$params[':offset'] = $offset;

// Get products
try {
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $products = [];
}

// Get categories for filter
$categories = getCategoriesFromDatabase();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - WattzElec</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 2rem;
            padding: 2rem 0;
        }

        .product-card {
            background: var(--bg-white);
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
            overflow: hidden;
        }

        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .product-info {
            padding: 1rem;
        }

        .product-name {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .product-category {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: var(--bg-accent-light);
            color: var(--text-accent);
            border-radius: 999px;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        .product-price {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .filters {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid var(--border-color);
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .page-link {
            padding: 0.5rem 1rem;
            border-radius: 4px;
            background: var(--bg-white);
            color: var(--text-primary);
            text-decoration: none;
            transition: background-color 0.2s;
        }

        .page-link.active {
            background: var(--bg-accent);
            color: white;
        }

        .page-link:hover:not(.active) {
            background: var(--bg-accent-light);
        }

        @media (max-width: 768px) {
            .filters {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container">
        <h1>Our Products</h1>

        <form class="filters" method="GET">
            <div class="filter-group">
                <label for="category">Category:</label>
                <select name="category" id="category" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat['id']) ?>" 
                                <?= $category_id == $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label for="sort">Sort by:</label>
                <select name="sort" id="sort" onchange="this.form.submit()">
                    <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Name (A-Z)</option>
                    <option value="name_desc" <?= $sort === 'name_desc' ? 'selected' : '' ?>>Name (Z-A)</option>
                    <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price (Low to High)</option>
                    <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price (High to Low)</option>
                </select>
            </div>

            <div class="filter-group">
                <input type="text" name="search" placeholder="Search products..." 
                       value="<?= htmlspecialchars($search) ?>" class="search-input">
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </form>

        <?php if (empty($products)): ?>
            <div class="empty-state">
                <p>No products found matching your criteria.</p>
            </div>
        <?php else: ?>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <img src="<?= htmlspecialchars($product['image_url']) ?>" 
                             alt="<?= htmlspecialchars($product['name']) ?>" 
                             class="product-image">
                        <div class="product-info">
                            <span class="product-category">
                                <?= htmlspecialchars($product['category_name']) ?>
                            </span>
                            <h3 class="product-name">
                                <?= htmlspecialchars($product['name']) ?>
                            </h3>
                            <p class="product-price">
                                KSh <?= number_format($product['price'], 2) ?>
                            </p>
                            <a href="product_details.php?id=<?= $product['id'] ?>" 
                               class="btn btn-primary">View Details</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?= $i ?><?= $category_id ? "&category=$category_id" : '' ?><?= $sort ? "&sort=$sort" : '' ?><?= $search ? "&search=" . urlencode($search) : '' ?>" 
                           class="page-link <?= $page === $i ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
