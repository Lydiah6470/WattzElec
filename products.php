<?php
session_start();
include 'includes/header.php';
include 'includes/db.php';

// Fetch all categories
$categories = $conn->query("SELECT category_id, name FROM category")->fetchAll();

// Get price range from database
$price_range = $conn->query("SELECT MIN(price) as min_price, MAX(price) as max_price FROM products")->fetch(PDO::FETCH_ASSOC);
$db_min_price = floor($price_range['min_price'] ?? 0);
$db_max_price = ceil($price_range['max_price'] ?? 100000);

// Fetch selected filters
$category_id = isset($_GET['category']) ? intval($_GET['category']) : 0;
$subcategory_ids = isset($_GET['subcategory']) ? array_map('intval', $_GET['subcategory']) : [];
$min_price = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? floatval($_GET['min_price']) : $db_min_price;
$max_price = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? floatval($_GET['max_price']) : $db_max_price;

// Build the query dynamically based on filters
$query = "
    SELECT p.product_id, p.name, p.image_1, p.price, p.discount, p.stock_quantity,
           p.price * (1 - COALESCE(p.discount, 0)/100) as final_price,
           c.name AS category_name, s.name AS subcategory_name
    FROM products p
    JOIN subcategories s ON p.subcategory_id = s.subcategory_id
    JOIN category c ON s.category_id = c.category_id
    WHERE 1=1
";

$params = [];
if ($category_id > 0) {
    $query .= " AND c.category_id = ?";
    $params[] = $category_id;
}
if (!empty($subcategory_ids)) {
    $placeholders = implode(',', array_fill(0, count($subcategory_ids), '?'));
    $query .= " AND s.subcategory_id IN ($placeholders)";
    $params = array_merge($params, $subcategory_ids);
}
if ($min_price > 0) {
    $query .= " AND p.price >= ?";
    $params[] = $min_price;
}
if ($max_price < 999999) {
    $query .= " AND p.price <= ?";
    $params[] = $max_price;
}

$query .= " ORDER BY p.product_id DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>
<div class="container-fluid">
    <div class="row">
        <!-- Filters Toggle Button (Visible on Small Screens) -->
        <div class="col-12 d-md-none mb-3">
            <button class="btn w-100" id="filters-toggle" style="background-color: var(--primary-color); color: white;">
                <i class="fas fa-filter me-2"></i> <span>Show Filters</span>
            </button>
        </div>

        <!-- Filters Section -->
        <div class="col-md-3 d-none d-md-block" id="filters-section">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-sliders-h me-2"></i>Filters</h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="filter-form">
                        <!-- Active Filters -->
                        <?php if ($category_id > 0 || !empty($subcategory_ids) || $min_price > 0 || $max_price < 999999): ?>                  
                        <?php endif; ?>

                        <!-- Category Filter -->
                        <div class="mb-4">
                            <h6 class="border-bottom pb-2">Categories</h6>
                            <?php foreach ($categories as $category): ?>
                                <div class="form-check py-1">
                                    <input class="form-check-input" type="radio" name="category" 
                                           value="<?= $category['category_id'] ?>" 
                                           id="category-<?= $category['category_id'] ?>" 
                                           <?= ($category['category_id'] == $category_id) ? 'checked' : '' ?>>
                                    <label class="form-check-label w-100" for="category-<?= $category['category_id'] ?>">
                                        <?= htmlspecialchars($category['name']) ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Subcategory Filter -->
                        <div class="mb-4" id="subcategory-filter">
                            <?php if ($category_id > 0): ?>
                                <h6 class="border-bottom pb-2">Subcategories</h6>
                                <?php
                                $subcategories = $conn->prepare("
                                    SELECT s.subcategory_id, s.name, COUNT(p.product_id) as product_count
                                    FROM subcategories s
                                    JOIN products p ON s.subcategory_id = p.subcategory_id
                                    WHERE s.category_id = ?
                                    GROUP BY s.subcategory_id, s.name
                                    ORDER BY product_count DESC
                                ");
                                $subcategories->execute([$category_id]);
                                foreach ($subcategories->fetchAll() as $subcategory): ?>
                                    <div class="form-check py-1">
                                        <input class="form-check-input" type="checkbox" 
                                               name="subcategory[]" 
                                               value="<?= $subcategory['subcategory_id'] ?>" 
                                               id="subcategory-<?= $subcategory['subcategory_id'] ?>" 
                                               <?= in_array($subcategory['subcategory_id'], $subcategory_ids) ? 'checked' : '' ?>>
                                        <label class="form-check-label w-100" for="subcategory-<?= $subcategory['subcategory_id'] ?>">
                                            <?= htmlspecialchars($subcategory['name']) ?>
                                            <span class="badge bg-light text-dark float-end"><?= $subcategory['product_count'] ?></span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Price Range Filter -->
                        <div class="mb-4">
                            <h6 class="border-bottom pb-2">Price Range</h6>
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="form-floating">
                                        <input type="number" name="min_price" class="form-control" 
                                               id="min-price" placeholder="Min" 
                                               value="<?= $min_price > 0 ? $min_price : '' ?>">
                                        <label for="min-price">Min (KSH)</label>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-floating">
                                        <input type="number" name="max_price" class="form-control" 
                                               id="max-price" placeholder="Max" 
                                               value="<?= $max_price < 999999 ? $max_price : '' ?>">
                                        <label for="max-price">Max (KSH)</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-2"></i>Apply Filters
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Product Grid -->
        <div class="col-md-9">
            <h2>Products</h2>
            <div class="row">
                <?php if (count($products) > 0): ?>
                    <?php foreach ($products as $product): ?>
                        <div class="col-md-4 col-sm-6 mb-4">
                            <div class="card h-100">
                                <img src="<?= htmlspecialchars($product['image_1'] ?? 'images/placeholder.jpg') ?>" class="card-img-top" alt="<?= htmlspecialchars($product['name']) ?>">
                                <?php if ($product['discount'] > 0): ?>
                                    <div class="position-absolute top-0 end-0 p-2">
                                        <span class="badge bg-danger"><?= $product['discount'] ?>% OFF</span>
                                    </div>
                                <?php endif; ?>
                                <div class="card-body d-flex flex-column justify-content-between">
                                    <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>

                                    <!-- Price and Discount -->
                                    <p class="card-text text-danger">
                                        <?php
                                        // Original price
                                        $originalPrice = $product['price'];
                                        
                                        // Display original price with strikethrough if there's a discount
                                        if ($product['discount'] > 0) {
                                            echo '<del class="text-muted" style="font-size: 0.8em;">KSH ' . number_format($product['price'], 2) . '</del><br>';
                                        }
                                        // Display final price
                                        echo '<span style="color: var(--primary-color); font-weight: bold;">KSH ' . number_format($product['final_price'], 2) . '</span>';
                                        ?>
                                    </p>

                                    <!-- Stock Status -->
                                    <p class="card-text text-muted">
                                        <?php
                                        $stock = $product['stock_quantity'] ?? 0;
                                        if ($stock <= 0) {
                                            echo '<span class="text-danger">Out of Stock</span>';
                                        } elseif ($stock <= 5) {
                                            echo '<span class="text-warning">Low Stock! Only ' . $stock . ' left</span>';
                                        } else {
                                            echo '<span class="text-success">In Stock (' . $stock . ' available)</span>';
                                        }
                                        ?>
                                    </p>

                                    <!-- View Details Button -->
                                    <a href="product_details.php?id=<?= $product['product_id'] ?>" class="btn btn-primary w-100 mt-auto">View Details</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No products found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const categoryRadios = document.querySelectorAll('input[name="category"]');
    const subcategoryFilter = document.getElementById('subcategory-filter');
    const filtersToggle = document.getElementById('filters-toggle');
    const filtersSection = document.getElementById('filters-section');
    const filterForm = document.querySelector('.filter-form');

    // Toggle Filters Section for Small Screens
    filtersToggle.addEventListener('click', function () {
        const toggleSpan = filtersToggle.querySelector('span');
        if (filtersSection.classList.contains('d-none')) {
            filtersSection.classList.remove('d-none');
            toggleSpan.textContent = 'Hide Filters';
            filtersSection.classList.add('mb-3');
        } else {
            filtersSection.classList.add('d-none');
            toggleSpan.textContent = 'Show Filters';
            filtersSection.classList.remove('mb-3');
        }
    });

    // Dynamically Load Subcategories
    categoryRadios.forEach(radio => {
        radio.addEventListener('change', async function () {
            const categoryId = this.value;
            if (categoryId) {
                try {
                    const response = await fetch(`ajax/get_subcategories.php?category_id=${categoryId}`);
                    const data = await response.json();
                    
                    let html = `
                        <div class="mb-4">
                            <h6 class="border-bottom pb-2">Subcategories</h6>
                            <div class="subcategory-list">
                    `;
                    
                    if (data.length > 0) {
                        data.forEach(subcategory => {
                            html += `
                                <div class="form-check py-1">
                                    <input class="form-check-input" type="checkbox" 
                                           name="subcategory[]" 
                                           value="${subcategory.subcategory_id}" 
                                           id="subcategory-${subcategory.subcategory_id}">
                                    <label class="form-check-label w-100" for="subcategory-${subcategory.subcategory_id}">
                                        ${subcategory.name}
                                        <span class="badge bg-light text-dark float-end">${subcategory.product_count}</span>
                                    </label>
                                </div>
                            `;
                        });
                    } else {
                        html += '<p class="text-muted">No subcategories found</p>';
                    }
                    
                    html += '</div></div>';
                    subcategoryFilter.innerHTML = html;
                } catch (error) {
                    console.error('Error loading subcategories:', error);
                    subcategoryFilter.innerHTML = '<div class="alert alert-danger">Error loading subcategories</div>';
                }
            } else {
                subcategoryFilter.innerHTML = '';
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>