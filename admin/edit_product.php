<?php
session_start();
// if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
//     header("Location: login.php");
//     exit;
// }

include 'includes/header.php';
include 'includes/db.php';

$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

// Fetch product details
$stmt = $conn->prepare("
    SELECT p.*, c.category_id, s.subcategory_id, c.name AS category_name, s.name AS subcategory_name
    FROM products p
    JOIN subcategories s ON p.subcategory_id = s.subcategory_id
    JOIN category c ON s.category_id = c.category_id
    WHERE p.product_id = ?
");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    die("Product not found.");
}

// Fetch categories and subcategories
$categories = $conn->query("SELECT category_id, name FROM category ORDER BY name")->fetchAll();
$subcategories = $conn->query("SELECT subcategory_id, name, category_id FROM subcategories ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize input
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $discount = floatval($_POST['discount']);
    $stock = intval($_POST['stock']);
    $subcategory_id = intval($_POST['subcategory_id']);

    // Handle image uploads
    $target_dir = "../uploads/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true); // Create directory if it doesn't exist
    }

    // Initialize image URLs with existing values
    $image_urls = [
        'image_1' => $_POST['existing_image_1'],
        'image_2' => $_POST['existing_image_2'],
        'image_3' => $_POST['existing_image_3'],
    ];

    // Process each image field
    $image_fields = ['image_1', 'image_2', 'image_3'];
    foreach ($image_fields as $index => $field) {
        if (isset($_FILES[$field]) && $_FILES[$field]['size'] > 0) { // If a new image is uploaded
            $target_file = $target_dir . basename($_FILES[$field]["name"]);
            $image_urls[$field] = "uploads/" . basename($_FILES[$field]["name"]);

            move_uploaded_file($_FILES[$field]["tmp_name"], $target_file);
        }
    }

    // Extract image URLs
    $image_1 = $image_urls['image_1'];
    $image_2 = $image_urls['image_2'];
    $image_3 = $image_urls['image_3'];

    // Update product details in the database
    $stmt = $conn->prepare("
        UPDATE products
        SET name=?, description=?, price=?, discount=?, stock_quantity=?, subcategory_id=?, image_1=?, image_2=?, image_3=?
        WHERE product_id=?
    ");
    $stmt->execute([$name, $description, $price, $discount, $stock, $subcategory_id, $image_1, $image_2, $image_3, $product_id]);

    header("Location: products.php");
    exit;
}
?>

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

.form-container {
    max-width: 800px;
    margin: 2rem auto;
    padding: 2rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.form-container h2 {
    color: var(--text-primary);
    margin-bottom: 2rem;
    font-size: 1.8rem;
    border-bottom: 2px solid var(--border-color);
    padding-bottom: 1rem;
}

.product-form {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    color: var(--text-primary);
    font-weight: 500;
}

.form-group input[type="text"],
.form-group input[type="number"],
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.2s ease;
}

.form-group input[type="file"] {
    width: 100%;
    padding: 0.5rem;
    border: 2px dashed var(--border-color);
    border-radius: 8px;
    cursor: pointer;
    transition: border-color 0.2s ease;
}

.form-group input[type="file"]:hover {
    border-color: var(--primary-color);
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
}

.btn-submit {
    grid-column: 1 / -1;
    background-color: var(--primary-color);
    color: white;
    padding: 1rem;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    margin-top: 1rem;
}

.btn-submit:hover {
    background-color: #324ab2;
    transform: translateY(-2px);
}

.preview-images {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-bottom: 1rem;
}

.preview-image {
    width: 100%;
    aspect-ratio: 1;
    object-fit: cover;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.success-message {
    background-color: #d4edda;
    color: #155724;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    animation: slideIn 0.3s ease;
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

@media (max-width: 768px) {
    .form-container {
        margin: 1rem;
        padding: 1rem;
    }

    .product-form {
        grid-template-columns: 1fr;
    }

    .preview-images {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="form-container">
    <h2>Edit Product</h2>

    <form method="POST" enctype="multipart/form-data" class="product-form">
        <!-- Hidden fields for existing image URLs -->
        <input type="hidden" name="existing_image_1" value="<?php echo htmlspecialchars($product['image_1'] ?? ''); ?>">
        <input type="hidden" name="existing_image_2" value="<?php echo htmlspecialchars($product['image_2'] ?? ''); ?>">
        <input type="hidden" name="existing_image_3" value="<?php echo htmlspecialchars($product['image_3'] ?? ''); ?>">



        <div class="form-group">
            <label for="name">Product Name</label>
            <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
        </div>

        <div class="form-group">
            <label for="price">Price (KSH)</label>
            <input type="number" step="0.01" name="price" id="price" value="<?php echo htmlspecialchars($product['price']); ?>" required>
        </div>

        <div class="form-group">
            <label for="discount">Discount (%)</label>
            <input type="number" step="0.01" name="discount" id="discount" value="<?php echo htmlspecialchars($product['discount'] ?? 0); ?>">
        </div>

        <div class="form-group">
            <label for="stock">Stock Quantity</label>
            <input type="number" name="stock" id="stock" min="0" value="<?php echo htmlspecialchars($product['stock_quantity'] ?? 0); ?>" required>
        </div>

        <div class="form-group">
            <label for="category">Category</label>
            <select id="category" name="category" onchange="loadSubcategories(this.value)" required>
                <option value="">Select Category</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['category_id']; ?>" <?php echo ($category['category_id'] == $product['category_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="subcategory">Subcategory</label>
            <select id="subcategory" name="subcategory_id" required>
                <option value="">Select Subcategory</option>
                <?php foreach ($subcategories as $subcategory): ?>
                    <option value="<?php echo $subcategory['subcategory_id']; ?>" <?php echo ($subcategory['subcategory_id'] == $product['subcategory_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($subcategory['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group full-width">
            <label for="description">Description</label>
            <textarea name="description" id="description" rows="4" required><?php echo htmlspecialchars($product['description']); ?></textarea>
        </div>

        <!-- New Image Upload Fields -->
        <div class="form-group">
            <label for="image_1">Upload New Main Image</label>
            <input type="file" name="image_1" id="image_1" accept="image/*">
        </div>

        <div class="form-group">
            <label for="image_2">Upload New Secondary Image</label>
            <input type="file" name="image_2" id="image_2" accept="image/*">
        </div>

        <div class="form-group">
            <label for="image_3">Upload New Additional Image</label>
            <input type="file" name="image_3" id="image_3" accept="image/*">
        </div>

        <button type="submit" class="btn-submit">Update Product</button>
    </form>
</div>

<script>
function loadSubcategories(categoryId) {
    const subcategorySelect = document.getElementById('subcategory');
    subcategorySelect.innerHTML = '<option value="">Select Subcategory</option>';

    if (!categoryId) return;

    // Convert categoryId to string for comparison
    categoryId = categoryId.toString();

    // Filter subcategories based on selected category
    const subcategories = <?php echo json_encode($subcategories); ?>;
    const filteredSubcategories = subcategories.filter(sub => sub.category_id.toString() === categoryId);

    // Add filtered subcategories to select
    filteredSubcategories.forEach(sub => {
        const option = document.createElement('option');
        option.value = sub.subcategory_id;
        option.textContent = sub.name;
        option.selected = sub.subcategory_id.toString() === '<?php echo $product['subcategory_id']; ?>';
        subcategorySelect.appendChild(option);
    });
}

// Initialize subcategories on page load
document.addEventListener('DOMContentLoaded', function() {
    const categorySelect = document.getElementById('category');
    if (categorySelect.value) {
        loadSubcategories(categorySelect.value);
    }
});
</script>

<?php include 'includes/footer.php'; ?>