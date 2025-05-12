<?php
session_start();
require_once 'includes/db.php';
include 'includes/header.php';

// Redirect to login if the user is not logged in or is not an admin
// if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
//     header("Location: ../login.php");
//     exit;
// }

// Handle product deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    $product_id = intval($_POST['product_id']);
    $query = "DELETE FROM products WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$product_id]);
    $success_message = "Product deleted successfully.";
}

// Get subcategory filter
$subcategory_id = isset($_GET['subcategory']) ? (int)$_GET['subcategory'] : 0;

// Get subcategory name if filter is active
$subcategory_name = '';
if ($subcategory_id > 0) {
    $stmt = $conn->prepare("SELECT name FROM subcategories WHERE subcategory_id = ?");
    $stmt->execute([$subcategory_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $subcategory_name = $result ? $result['name'] : '';
}

// Fetch products with category and subcategory details
$query = "
    SELECT p.product_id, p.name, p.price, p.discount, 
           ROUND(p.price - (p.price * p.discount / 100), 2) as discounted_price,
           p.stock_quantity as stock, p.image_1,
           c.name AS category_name, s.name AS subcategory_name,
           p.status, p.featured
    FROM products p
    JOIN subcategories s ON p.subcategory_id = s.subcategory_id
    JOIN category c ON s.category_id = c.category_id
";

// Add subcategory filter if specified
if ($subcategory_id > 0) {
    $query .= " WHERE p.subcategory_id = ?";
}

$query .= " ORDER BY p.product_id DESC";

$stmt = $conn->prepare($query);

// Execute with or without subcategory parameter
if ($subcategory_id > 0) {
    $stmt->execute([$subcategory_id]);
} else {
    $stmt->execute();
}

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Function to get stock status class
function getStockStatusClass($stock) {
    if ($stock <= 0) return 'out-of-stock';
    if ($stock < 10) return 'low-stock';
    return 'in-stock';
}
?>

<!-- Link to the CSS file -->
<link rel="stylesheet" href="assets/css/inventory.css">
<style>
:root {
    --primary-color: #4361ee;
    --success-color: #2ecc71;
    --warning-color: #f1c40f;
    --danger-color: #e74c3c;
    --info-color: #3498db;
    --text-primary: #2d3436;
    --text-secondary: #636e72;
    --background-light: #f8f9fa;
    --border-color: #e9ecef;
}

.products-container {
    padding: 2rem;
    background-color: var(--background-light);
    min-height: 100vh;
}

.content-wrapper {
    max-width: 1400px;
    margin: 0 auto;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.page-title {
    font-size: 2rem;
    color: var(--text-primary);
    margin: 0;
}

.add-product-btn {
    background-color: var(--primary-color);
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s ease;
    border: none;
    cursor: pointer;
}

.add-product-btn:hover {
    background-color: #324ab2;
    transform: translateY(-2px);
}

.add-product-btn::before {
    content: '+';
    font-size: 1.2rem;
    font-weight: bold;
}

.products-grid {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

.products-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.products-table th,
.products-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid var(--border-color);
}

.products-table th {
    background-color: var(--background-light);
    color: var(--text-primary);
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
}

.products-table tbody tr {
    transition: background-color 0.2s ease;
}

.products-table tbody tr:hover {
    background-color: var(--background-light);
}

.product-image {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 8px;
    border: 1px solid var(--border-color);
    object-fit: cover;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.product-name {
    font-weight: 500;
    color: var(--text-primary);
}

.category-badge {
    background-color: #e3e7f9;
    color: var(--primary-color);
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.85rem;
}

.price {
    font-weight: 600;
    color: var(--text-primary);
}

.discount {
    color: var(--success-color);
    font-weight: 500;
}

.stock-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
}

.in-stock {
    background-color: #d4edda;
    color: #155724;
}

.low-stock {
    background-color: #fff3cd;
    color: #856404;
}

.products-table td {
    font-size: 0.95rem;
    vertical-align: middle;
}

.products-table .category,
.products-table .subcategory {
    color: #666;
    font-size: 0.9rem;
}

.products-table .price {
    font-weight: 600;
    color: #2c3e50;
}

.products-table .stock {
    font-size: 0.9rem;
    color: #666;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.stock-warning {
    color: var(--warning-color);
    font-size: 0.8rem;
    font-weight: 500;
}

.out-of-stock {
    background-color: #fee2e2;
    color: #991b1b;
}

.low-stock {
    background-color: #fff3cd;
    color: #856404;
}

.in-stock {
    background-color: #d1fae5;
    color: #065f46;
}

.new-price {
    color: var(--success-color);
    font-weight: 600;
}

.discount {
    color: var(--danger-color);
    font-weight: 500;
}

.products-table .status {
    font-size: 0.9rem;
}

.products-table .status.status-in_stock {
    color: #27ae60;
}

.products-table .status.status-out_of_stock {
    color: #e74c3c;
}

.products-table .status.status-discontinued {
    color: #95a5a6;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
}

.btn {
    padding: 0.5rem 1rem;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    font-size: 0.9rem;
    transition: all 0.2s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.btn-edit {
    background-color: #fff3cd;
    color: #856404;
}

.btn-edit:hover {
    background-color: #ffe69c;
}

.btn-delete {
    background-color: #f8d7da;
    color: #721c24;
}

.btn-delete:hover {
    background-color: #f5c6cb;
}

.success-message {
    background-color: #d4edda;
    color: #155724;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    animation: slideIn 0.3s ease;
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: var(--text-secondary);
}

@keyframes slideIn {
    from {
        transform: translateY(-10px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

@media (max-width: 1024px) {
    .products-table {
        display: block;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .page-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
}

@media (max-width: 768px) {
    .products-container {
        padding: 1rem;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<div class="products-container">
    <div class="content-wrapper">
        <div class="page-header">
            <div>
                <h2 class="page-title">Manage Products</h2>
                <?php if ($subcategory_name): ?>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="products.php">All Products</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($subcategory_name) ?></li>
                    </ol>
                </nav>
                <?php endif; ?>
            </div>
            <a href="add_product.php<?= $subcategory_id ? '?subcategory='.$subcategory_id : '' ?>" class="add-product-btn">
                Add New Product
            </a>
        </div>

        <?php if (isset($success_message)): ?>
        <div class="success-message">
            <?php echo htmlspecialchars($success_message); ?>
        </div>
        <?php endif; ?>

        <?php if (count($products) > 0): ?>
            <div class="products-grid">
                <table class="products-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Subcategory</th>
                            <th>Marked Price</th>
                            <th>Discount</th>
                            <th>New Price</th>
                            <th>Stock Level</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <?php if ($product['image_1']): ?>
                                        <img src="../<?php echo htmlspecialchars($product['image_1']); ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                             class="product-image">
                                    <?php else: ?>
                                        <div class="no-image">No Image</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="product-name"><?php echo htmlspecialchars($product['name']); ?></span>
                                </td>
                                <td class="category"><?php echo htmlspecialchars($product['category_name'] ?? 'No Category'); ?></td>
                                <td class="subcategory"><?php echo htmlspecialchars($product['subcategory_name'] ?? 'No Subcategory'); ?></td>
                                <td class="price">KSh <?php echo number_format($product['price'], 2); ?></td>
                                <td class="discount"><?php echo $product['discount'] ? number_format($product['discount'], 2) . '%' : '-'; ?></td>
                                <td class="price new-price"><?php echo $product['discount'] ? 'KSh ' . number_format($product['discounted_price'], 2) : '-'; ?></td>
                                <td class="stock <?php echo getStockStatusClass($product['stock']); ?>">
                                    <?php echo htmlspecialchars($product['stock']); ?> units
                                    <?php if ($product['stock'] < 10): ?>
                                        <span class="stock-warning">(Low Stock)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="status status-<?php echo $product['status']; ?>">
                                    <?php echo ucwords(str_replace('_', ' ', $product['status'])); ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="edit_product.php?product_id=<?php echo $product['product_id']; ?>" 
                                           class="btn btn-edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button onclick="deleteProduct(<?php echo $product['product_id']; ?>)" 
                                                class="btn btn-delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p>No products found in inventory.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>