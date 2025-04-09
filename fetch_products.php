<?php
include 'includes/db.php';

// Get the category ID from the query string
$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;

if ($category_id > 0) {
    // Fetch products for the selected category
    $query = "
        SELECT p.id, p.name, p.image_url, p.price, p.discount, p.stock
        FROM products p
        JOIN subcategories s ON p.subcategory_id = s.id
        JOIN category c ON s.category_id = c.id
        WHERE c.id = ?
    ";
    $stmt = $conn->prepare($query);
    $stmt->execute([$category_id]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($products) > 0) {
        // Display products in a grid layout
        foreach ($products as $product) {
            echo '<div class="col-md-3 mb-4">';
            echo '<div class="card h-100">';
            echo '<img src="' . htmlspecialchars($product['image_url']) . '" class="card-img-top" alt="' . htmlspecialchars($product['name']) . '">';
            echo '<div class="card-body">';
            echo '<h5 class="card-title">' . htmlspecialchars($product['name']) . '</h5>';
            echo '<p class="card-text text-danger">KSH ' . number_format($product['price'], 2) . '</p>';
            echo '<a href="product_details.php?id=' . $product['id'] . '" class="btn btn-primary w-100">View Details</a>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
        }
    } else {
        echo '<p>No products found in this category.</p>';
    }
} else {
    echo '<p>Please select a valid category.</p>';
}
?>