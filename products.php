<?php
session_start();
include 'includes/header.php';
include 'includes/db.php';

// Fetch all categories
$categories = $conn->query("SELECT * FROM category")->fetchAll();

// Fetch selected filters
$category_id = isset($_GET['category']) ? intval($_GET['category']) : 0;
$subcategory_ids = isset($_GET['subcategory']) ? array_map('intval', $_GET['subcategory']) : [];
$min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 999999;

// Build the query dynamically based on filters
$query = "
    SELECT p.id, p.name, p.image_url, p.price, p.discount, p.stock, c.name AS category_name, s.name AS subcategory_name
    FROM products p
    JOIN subcategories s ON p.subcategory_id = s.id
    JOIN category c ON s.category_id = c.id
    WHERE 1=1
";

$params = [];
if ($category_id > 0) {
    $query .= " AND c.id = ?";
    $params[] = $category_id;
}
if (!empty($subcategory_ids)) {
    $placeholders = implode(',', array_fill(0, count($subcategory_ids), '?'));
    $query .= " AND s.id IN ($placeholders)";
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

$query .= " ORDER BY p.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>
<div class="container-fluid">
    <div class="row">
        <!-- Filters Toggle Button (Visible on Small Screens) -->
        <div class="col-12 d-md-none text-end mb-3">
            <button class="btn btn-primary" id="filters-toggle">Show Filters</button>
        </div>

        <!-- Filters Section -->
        <div class="col-md-3 d-none d-md-block" id="filters-section">
            <h4>Filters</h4>
            <form method="GET" class="filter-form">
                <!-- Category Filter -->
                <div class="mb-3">
                    <label><strong>Category:</strong></label>
                    <?php foreach ($categories as $category): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="category" value="<?php echo $category['id']; ?>" id="category-<?php echo $category['id']; ?>" <?php echo ($category['id'] == $category_id) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="category-<?php echo $category['id']; ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Subcategory Filter (Dynamically loaded via AJAX) -->
                <div class="mb-3" id="subcategory-filter">
                    <?php if ($category_id > 0): ?>
                        <label><strong>Subcategory:</strong></label>
                        <?php
                        // Fetch subcategories with products in the selected category
                        $subcategories = $conn->prepare("
                            SELECT s.id, s.name
                            FROM subcategories s
                            JOIN products p ON s.id = p.subcategory_id
                            WHERE s.category_id = ?
                            GROUP BY s.id
                        ");
                        $subcategories->execute([$category_id]);
                        foreach ($subcategories->fetchAll() as $subcategory): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="subcategory[]" value="<?php echo $subcategory['id']; ?>" id="subcategory-<?php echo $subcategory['id']; ?>" <?php echo in_array($subcategory['id'], $subcategory_ids) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="subcategory-<?php echo $subcategory['id']; ?>">
                                    <?php echo htmlspecialchars($subcategory['name']); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Price Range Filter -->
                <div class="mb-3">
                    <label><strong>Price Range (KSH):</strong></label>
                    <div class="input-group">
                        <input type="number" name="min_price" id="min_price" value="<?php echo $min_price; ?>" min="0" class="form-control">
                        <span class="input-group-text">to</span>
                        <input type="number" name="max_price" id="max_price" value="<?php echo $max_price; ?>" max="999999" class="form-control">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary mt-2">Apply Filters</button>
            </form>
        </div>

        <!-- Product Grid -->
        <div class="col-md-9">
            <h2>Products</h2>
            <div class="row">
                <?php if (count($products) > 0): ?>
                    <?php foreach ($products as $product): ?>
                        <div class="col-md-4 col-sm-6 mb-4">
                            <div class="card h-100">
                                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <div class="card-body d-flex flex-column justify-content-between">
                                    <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>

                                    <!-- Price and Discount -->
                                    <p class="card-text text-danger">
                                        <?php
                                        // Original price
                                        $originalPrice = $product['price'];
                                        
                                        // Discounted price
                                        $discountPercentage = $product['discount'] ?? 0;
                                        $discountedPrice = $originalPrice - ($originalPrice * ($discountPercentage / 100));

                                        // Display discounted price first
                                        echo '<span style="color: red; font-weight: bold;">KSH ' . number_format($discountedPrice, 2) . '</span>';
                                        
                                        // Display original price with strikethrough and smaller font size
                                        if ($discountPercentage > 0) {
                                            echo ' <del style="font-size: 0.8em; color: gray;">KSH ' . number_format($originalPrice, 2) . '</del>';
                                        }
                                        ?>
                                    </p>

                                    <!-- Stock Status -->
                                    <p class="card-text text-muted">
                                        <?php
                                        $stock = $product['stock'] ?? 0;
                                        echo $stock > 0 ? "In Stock ({$stock} left)" : "Out of Stock";
                                        ?>
                                    </p>

                                    <!-- View Details Button -->
                                    <a href="product_details.php?id=<?php echo $product['id']; ?>" class="btn btn-primary w-100 mt-auto">View Details</a>
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

    // Toggle Filters Section for Small Screens
    filtersToggle.addEventListener('click', function () {
        if (filtersSection.classList.contains('d-none')) {
            filtersSection.classList.remove('d-none');
            filtersToggle.textContent = 'Hide Filters';
        } else {
            filtersSection.classList.add('d-none');
            filtersToggle.textContent = 'Show Filters';
        }
    });

    // Dynamically Load Subcategories
    categoryRadios.forEach(radio => {
        radio.addEventListener('change', function () {
            const categoryId = this.value;
            if (categoryId) {
                fetch(`get_subcategories_with_products.php?category_id=${categoryId}`)
                    .then(response => response.json())
                    .then(data => {
                        let html = '<label><strong>Subcategory:</strong></label>';
                        data.forEach(subcategory => {
                            html += `
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="subcategory[]" value="${subcategory.id}" id="subcategory-${subcategory.id}">
                                    <label class="form-check-label" for="subcategory-${subcategory.id}">
                                        ${subcategory.name}
                                    </label>
                                </div>
                            `;
                        });
                        subcategoryFilter.innerHTML = html;
                    })
                    .catch(error => {
                        console.error('Error loading subcategories:', error);
                    });
            } else {
                // Clear subcategory filter if no category is selected
                subcategoryFilter.innerHTML = '';
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>