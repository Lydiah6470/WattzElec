<?php
session_start();
include 'includes/header.php';
include 'includes/db.php';

// // Redirect to login if the user is not logged in or is not an admin
// if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
//     header("Location: ../login.php");
//     exit;
// }

// Handle review deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_review'])) {
    $review_id = intval($_POST['review_id']);
    try {
        $stmt = $conn->prepare("DELETE FROM reviews WHERE review_id = ?");
        $stmt->execute([$review_id]);
        $_SESSION['alert'] = ['type' => 'success', 'message' => 'Review deleted successfully'];
    } catch (PDOException $e) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Error deleting review: ' . $e->getMessage()];
    }
    header("Location: reviews.php");
    exit;
}

// Fetch all reviews with product and user information
$query = "
    SELECT r.*, p.name as product_name, p.image_1 as product_image, 
           u.username, o.order_id
    FROM reviews r
    JOIN products p ON r.product_id = p.product_id
    JOIN users u ON r.user_id = u.user_id
    JOIN user_order o ON r.order_id = o.order_id
    ORDER BY r.review_date DESC
";

try {
    $stmt = $conn->query($query);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Error fetching reviews: ' . $e->getMessage()];
    $reviews = [];
}

// Calculate review statistics
$stats = [
    'total' => count($reviews),
    'average' => 0,
    'ratings' => array_fill(1, 5, 0)
];

if (!empty($reviews)) {
    $total_rating = 0;
    foreach ($reviews as $review) {
        $total_rating += $review['rating'];
        $stats['ratings'][$review['rating']]++;
    }
    $stats['average'] = round($total_rating / count($reviews), 1);
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Customer Reviews</h1>
    
    <?php if (isset($_SESSION['alert'])): ?>
        <div class="alert alert-<?php echo $_SESSION['alert']['type']; ?> alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['alert']['message'];
            unset($_SESSION['alert']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Review Statistics -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <h2 class="mb-0"><?php echo $stats['total']; ?></h2>
                    <div>Total Reviews</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-dark mb-4">
                <div class="card-body">
                    <h2 class="mb-0"><?php echo $stats['average']; ?></h2>
                    <div>Average Rating</div>
                </div>
            </div>
        </div>
        <div class="col-xl-6 col-md-12">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Rating Distribution</h5>
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <?php 
                        $count = $stats['ratings'][$i];
                        $percentage = $stats['total'] > 0 ? ($count / $stats['total'] * 100) : 0;
                        ?>
                        <div class="d-flex align-items-center mb-1">
                            <div class="text-warning me-2"><?php echo $i; ?> ★</div>
                            <div class="progress flex-grow-1" style="height: 8px;">
                                <div class="progress-bar bg-warning" role="progressbar" 
                                     style="width: <?php echo $percentage; ?>%" 
                                     aria-valuenow="<?php echo $percentage; ?>" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100"></div>
                            </div>
                            <div class="ms-2 small"><?php echo $count; ?></div>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Reviews Table -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-comments me-1"></i>
            All Reviews
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="reviewsTable" class="table table-striped">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Customer</th>
                            <th>Rating</th>
                            <th>Review</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reviews as $review): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="../<?php echo htmlspecialchars($review['product_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($review['product_name']); ?>"
                                             class="me-2" style="width: 50px; height: 50px; object-fit: cover;">
                                        <div>
                                            <div><?php echo htmlspecialchars($review['product_name']); ?></div>
                                            <small class="text-muted">Order #<?php echo $review['order_id']; ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($review['username']); ?></td>
                                <td>
                                    <div class="text-warning">
                                        <?php 
                                        for ($i = 1; $i <= 5; $i++) {
                                            echo $i <= $review['rating'] ? '★' : '☆';
                                        }
                                        ?>
                                    </div>
                                </td>
                                <td><?php echo nl2br(htmlspecialchars($review['comment'])); ?></td>
                                <td><?php echo date('M j, Y', strtotime($review['review_date'])); ?></td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this review?');">
                                        <input type="hidden" name="review_id" value="<?php echo $review['review_id']; ?>">
                                        <button type="submit" name="delete_review" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#reviewsTable').DataTable({
        order: [[4, 'desc']], // Sort by date by default
        pageLength: 10,
        responsive: true
    });
});
</script>

<?php include 'includes/footer.php'; ?>
