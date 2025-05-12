<?php
session_start();
// if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
//     header("Location: login.php");
//     exit;
// }

include 'includes/header.php';
include 'includes/db.php';

// Get subcategory from URL if provided
$subcategory_id = isset($_GET['subcategory']) ? (int)$_GET['subcategory'] : 0;

// If subcategory is provided, get its details and category
$category_id = 0;
$subcategory_name = '';
if ($subcategory_id > 0) {
    $stmt = $conn->prepare("
        SELECT s.*, c.category_id, c.name as category_name 
        FROM subcategories s 
        JOIN category c ON s.category_id = c.category_id 
        WHERE s.subcategory_id = ?");
    $stmt->execute([$subcategory_id]);
    $subcategory = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($subcategory) {
        $category_id = $subcategory['category_id'];
        $subcategory_name = $subcategory['name'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize input
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $discount = floatval($_POST['discount']);
    $stock_quantity = intval($_POST['stock_quantity']);
    $status = $_POST['status'];
    $subcategory_id = intval($_POST['subcategory_id']);

    // Handle image uploads
    $target_dir = "../uploads/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // Initialize image URLs
    $image_urls = [];
    $image_fields = ['image_1', 'image_2', 'image_3'];

    foreach ($image_fields as $field) {
        if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
            $file_extension = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
            $new_filename = uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $new_filename;
            
            if (move_uploaded_file($_FILES[$field]['tmp_name'], $target_file)) {
                $image_urls[] = "uploads/" . $new_filename;
            } else {
                $error = "Error uploading " . htmlspecialchars($field) . ".";
                break;
            }
        } else {
            $image_urls[] = null;
        }
    }

    if (!isset($error)) {
        // Insert product into database
        $stmt = $conn->prepare("
            INSERT INTO products (name, description, price, discount, stock_quantity, status, subcategory_id, image_1, image_2, image_3)
            VALUES (:name, :description, :price, :discount, :stock_quantity, :status, :subcategory_id, :image_1, :image_2, :image_3)
        ");

        try {
            // Prepare the image values
            $image1 = $image_urls[0] ?? null;
            $image2 = $image_urls[1] ?? null;
            $image3 = $image_urls[2] ?? null;

            // Bind all parameters
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':price', $price);
            $stmt->bindParam(':discount', $discount);
            $stmt->bindParam(':stock_quantity', $stock_quantity);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':subcategory_id', $subcategory_id);
            $stmt->bindParam(':image_1', $image1);
            $stmt->bindParam(':image_2', $image2);
            $stmt->bindParam(':image_3', $image3);

            // Execute the statement
            $stmt->execute();
            header("Location: products.php?success=1");
            exit;
        } catch (PDOException $e) {
            $error = "Error adding product: " . $e->getMessage();
        }
    }
}
?>

<style>
:root {
    --primary-color: #4361ee;
    --secondary-color: #3f37c9;
    --success-color: #2ecc71;
    --warning-color: #f1c40f;
    --danger-color: #e74c3c;
    --text-color: #2d3436;
    --light-bg: #f8f9fa;
    --border-color: #dee2e6;
}

.form-container {
    max-width: 800px;
    margin: 2rem auto;
    padding: 2rem;
    background: white;
    border-radius: 1rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.form-title {
    color: var(--primary-color);
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid var(--border-color);
}

.product-form {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    color: var(--text-color);
    font-weight: 500;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: 0.5rem;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
}

.image-preview {
    width: 100%;
    height: 200px;
    margin-top: 1rem;
    border: 2px dashed var(--border-color);
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background-size: cover;
    background-position: center;
    color: var(--text-color);
    font-size: 0.9rem;
}

.btn-submit {
    grid-column: 1 / -1;
    background-color: var(--primary-color);
    color: white;
    padding: 1rem;
    border: none;
    border-radius: 0.5rem;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-submit:hover {
    background-color: var(--secondary-color);
    transform: translateY(-2px);
}

.alert {
    padding: 1rem;
    border-radius: 0.5rem;
    margin-bottom: 1.5rem;
}

.alert-danger {
    background-color: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

@media (max-width: 768px) {
    .product-form {
        grid-template-columns: 1fr;
    }

    .form-container {
        margin: 1rem;
        padding: 1rem;
    }
}
</style>

<div class="form-container">
    <h2 class="form-title">Add New Product</h2>
    
    <?php if (isset($error)): ?>
    <div class="alert alert-danger">
        <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="product-form">
        <div class="form-group">
            <label class="form-label" for="name">Product Name</label>
            <input type="text" class="form-control" name="name" id="name" required 
                   value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
        </div>

        <div class="form-group">
            <label class="form-label" for="price">Price (KSH)</label>
            <input type="number" class="form-control" step="0.01" name="price" id="price" required 
                   value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>">
        </div>

        <div class="form-group">
            <label class="form-label" for="discount">Discount (%)</label>
            <input type="number" class="form-control" step="0.01" name="discount" id="discount" value="<?php echo isset($_POST['discount']) ? htmlspecialchars($_POST['discount']) : '0'; ?>">
        </div>

        <div class="form-group">
            <label class="form-label" for="stock">Stock Quantity</label>
            <input type="number" class="form-control" name="stock_quantity" id="stock_quantity" min="0" required 
                   value="<?php echo isset($_POST['stock_quantity']) ? htmlspecialchars($_POST['stock_quantity']) : '0'; ?>">
        </div>

        <div class="form-group">
            <label class="form-label" for="status">Status</label>
            <select class="form-control" name="status" id="status" required>
                <option value="in_stock" <?php echo (isset($_POST['status']) && $_POST['status'] == 'in_stock') ? 'selected' : ''; ?>>In Stock</option>
                <option value="out_of_stock" <?php echo (isset($_POST['status']) && $_POST['status'] == 'out_of_stock') ? 'selected' : ''; ?>>Out of Stock</option>
                <option value="discontinued" <?php echo (isset($_POST['status']) && $_POST['status'] == 'discontinued') ? 'selected' : ''; ?>>Discontinued</option>
            </select>
        </div>

        <input type="hidden" name="subcategory_id" value="<?php echo $subcategory_id; ?>">
        <?php if ($subcategory_id > 0): ?>
            <div class="form-group">
                <label class="form-label">Category</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($subcategory['category_name']); ?>" readonly>
            </div>
            <div class="form-group">
                <label class="form-label">Subcategory</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($subcategory_name); ?>" readonly>
            </div>
        <?php endif; ?>

        <div class="form-group full-width">
            <label class="form-label" for="description">Product Details</label>
            <small class="form-text text-muted">Enter product description, features, and technical details. You can include both general information and specifications.</small>
            <textarea class="form-control" name="description" id="description" rows="6" required 
                placeholder="Example:
This LED bulb provides bright, energy-efficient lighting perfect for home or office use. Features adjustable brightness and long-lasting performance.

Specifications:
- Wattage: 9W
- Voltage: 220-240V
- Base Type: E27
- Color Temperature: 6500K
- Lifespan: 25000 hours
- Dimensions: 60mm x 120mm"><?php 
                echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; 
            ?></textarea>
        </div>

        <div class="form-group">
            <label class="form-label" for="image_1">Main Product Image</label>
            <input type="file" class="form-control" name="image_1" id="image_1" accept="image/*" required 
                   onchange="previewImage(this, 'preview1')">
            <div id="preview1" class="image-preview">No image selected</div>
        </div>

        <div class="form-group">
            <label class="form-label" for="image_2">Secondary Image (Optional)</label>
            <input type="file" class="form-control" name="image_2" id="image_2" accept="image/*"
                   onchange="previewImage(this, 'preview2')">
            <div id="preview2" class="image-preview">No image selected</div>
        </div>

        <div class="form-group">
            <label class="form-label" for="image_3">Additional Image (Optional)</label>
            <input type="file" class="form-control" name="image_3" id="image_3" accept="image/*"
                   onchange="previewImage(this, 'preview3')">
            <div id="preview3" class="image-preview">No image selected</div>
        </div>

        <button type="submit" class="btn-submit">Add Product</button>
    </form>
</div>

<script>
function loadSubcategories(categoryId) {
    const subcategoryDropdown = document.getElementById('subcategory_id');
    subcategoryDropdown.innerHTML = '<option value="">Loading...</option>';

    fetch(`get_subcategories.php?category_id=${categoryId}`)
        .then(response => response.json())
        .then(data => {
            subcategoryDropdown.innerHTML = '<option value="">Select Subcategory</option>';
            data.forEach(subcategory => {
                const option = document.createElement('option');
                option.value = subcategory.id;
                option.textContent = subcategory.name;
                subcategoryDropdown.appendChild(option);
            });
        })
        .catch(error => {
            subcategoryDropdown.innerHTML = '<option value="">Error loading subcategories</option>';
        });
}

function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.style.backgroundImage = `url(${e.target.result})`;
            preview.innerHTML = '';
        }
        
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.style.backgroundImage = 'none';
        preview.innerHTML = 'No image selected';
    }
}
</script>

<?php include 'includes/footer.php'; ?>