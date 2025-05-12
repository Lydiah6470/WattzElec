<?php
require_once 'includes/db.php';

// Get selected category and subcategory
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// Fetch subcategories for the selected category
$subcategories = [];
if ($category_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM subcategories WHERE category_id = ? ORDER BY name");
    $stmt->execute([$category_id]);
    $subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get category name
$category_name = '';
if ($category_id > 0) {
    $stmt = $conn->prepare("SELECT name FROM category WHERE category_id = ?");
    $stmt->execute([$category_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $category_name = $result ? $result['name'] : '';
}

include 'includes/header.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center mb-4">
        <div>
            <h1 class="h2 mb-0">Inventory Management</h1>
            <?php if ($category_name): ?>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="inventory.php">All Categories</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($category_name) ?></li>
                </ol>
            </nav>
            <?php endif; ?>
        </div>
        <?php if ($category_id > 0): ?>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSubcategoryModal">
            <i class="fas fa-plus me-2"></i>Add Subcategory
        </button>
        <?php endif; ?>
    </div>

    <?php if ($category_id > 0): ?>
    <!-- Subcategories Grid -->
    <div class="row g-4 mb-4">
        <?php if (empty($subcategories)): ?>
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                    <h5 class="mb-2">No subcategories found</h5>
                    <p class="text-muted mb-0">Click the 'Add Subcategory' button to create one.</p>
                </div>
            </div>
        </div>
        <?php else: ?>
            <?php foreach ($subcategories as $subcategory): ?>
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card h-100 border shadow-sm">
                    <div class="position-relative">
                        <?php if (!empty($subcategory['image'])): ?>
                        <img src="..<?= htmlspecialchars($subcategory['image']) ?>" 
                             class="card-img-top" 
                             style="height: 200px; object-fit: cover;" 
                             alt="<?= htmlspecialchars($subcategory['name']) ?>">
                        <?php else: ?>
                        <div class="bg-light d-flex align-items-center justify-content-center" 
                             style="height: 200px;">
                            <i class="fas fa-image fa-3x text-muted"></i>
                        </div>
                        <?php endif; ?>
                        <div class="position-absolute top-0 end-0 p-3">
                            <div class="btn-group shadow-sm">
                                <button type="button" 
                                        class="btn btn-light btn-sm edit-subcategory" 
                                        data-id="<?= $subcategory['subcategory_id'] ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" 
                                        class="btn btn-light btn-sm delete-subcategory" 
                                        data-id="<?= $subcategory['subcategory_id'] ?>">
                                    <i class="fas fa-trash text-danger"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body border-top">
                        <h5 class="card-title mb-2"><?= htmlspecialchars($subcategory['name']) ?></h5>
                        <?php if (!empty($subcategory['description'])): ?>
                        <p class="card-text text-muted"><?= htmlspecialchars($subcategory['description']) ?></p>
                        <?php else: ?>
                        <p class="card-text text-muted fst-italic">No description available</p>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-white border-top">
                        <a href="products.php?subcategory=<?= $subcategory['subcategory_id'] ?>" 
                           class="btn btn-primary w-100">
                            <i class="fas fa-box me-2"></i>View Products
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="alert alert-info" role="alert">
        <i class="fas fa-info-circle me-2"></i>
        Please select a category from the sidebar to manage its subcategories.
    </div>
    <?php endif; ?>
</main>

<!-- Add Subcategory Modal -->
<div class="modal fade" id="addSubcategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Subcategory to <?= htmlspecialchars($category_name) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="process_subcategory.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="category_id" value="<?= $category_id ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="subcategoryName" class="form-label">Subcategory Name</label>
                        <input type="text" class="form-control" id="subcategoryName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="subcategoryDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="subcategoryDescription" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="subcategoryImage" class="form-label">Image</label>
                        <input type="file" class="form-control" id="subcategoryImage" name="image" accept="image/*">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Subcategory</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Subcategory Modal -->
<div class="modal fade" id="editSubcategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Subcategory</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="process_subcategory.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="subcategory_id" id="editSubcategoryId">
                <input type="hidden" name="category_id" value="<?= $category_id ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="editSubcategoryName" class="form-label">Subcategory Name</label>
                        <input type="text" class="form-control" id="editSubcategoryName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editSubcategoryDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="editSubcategoryDescription" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="editSubcategoryImage" class="form-label">Image</label>
                        <input type="file" class="form-control" id="editSubcategoryImage" name="image" accept="image/*">
                        <div id="currentImagePreview" class="mt-2"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Edit Subcategory
    const editButtons = document.querySelectorAll('.edit-subcategory');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const subcategoryId = this.dataset.id;
            const subcategoryName = this.closest('.card').querySelector('.card-title').textContent;
            const subcategoryDesc = this.closest('.card').querySelector('.card-text')?.textContent || '';
            
            document.getElementById('editSubcategoryId').value = subcategoryId;
            document.getElementById('editSubcategoryName').value = subcategoryName;
            document.getElementById('editSubcategoryDescription').value = subcategoryDesc;
            
            const modal = new bootstrap.Modal(document.getElementById('editSubcategoryModal'));
            modal.show();
        });
    });

    // Delete Subcategory
    const deleteButtons = document.querySelectorAll('.delete-subcategory');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const subcategoryId = this.dataset.id;
            if (confirm('Are you sure you want to delete this subcategory?')) {
                window.location.href = `process_subcategory.php?action=delete&id=${subcategoryId}&category_id=<?= $category_id ?>`;
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
