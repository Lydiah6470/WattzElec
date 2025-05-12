<?php
session_start();
include 'includes/header.php';
include 'includes/db.php';

// // Redirect if not admin
// if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
//     header("Location: ../login.php");
//     exit;
// }

// Initialize variables with default values
$totalOrders = 0;
$pendingOrders = 0;
$totalProducts = 0;
$lowStockProducts = 0;
$totalCategories = 0;
$totalCustomers = 0;
$recentOrders = [];
$topProducts = [];

// Get dashboard statistics
try {
    // Total Orders
    $stmt = $conn->query("SELECT COUNT(*) as total FROM user_order");
    $totalOrders = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Pending Orders
    $stmt = $conn->query("SELECT COUNT(*) as pending FROM user_order WHERE status = 'pending'");
    $pendingOrders = $stmt->fetch(PDO::FETCH_ASSOC)['pending'] ?? 0;

    // Total Products
    $stmt = $conn->query("SELECT COUNT(*) as total FROM products");
    $totalProducts = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Low Stock Products
    $stmt = $conn->query("SELECT COUNT(*) as low_stock FROM products WHERE stock_quantity <= 5 AND status = 'in_stock'");
    $lowStockProducts = $stmt->fetch(PDO::FETCH_ASSOC)['low_stock'] ?? 0;

    // Total Categories
    $stmt = $conn->query("SELECT COUNT(*) as total FROM category");
    $totalCategories = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Total Customers
    $stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'customer'");
    $totalCustomers = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Recent Orders
    $stmt = $conn->query("SELECT o.order_id, o.total_amount, o.status, o.order_date, u.username 
                         FROM user_order o 
                         JOIN users u ON o.user_id = u.user_id 
                         ORDER BY o.order_date DESC LIMIT 5");
    $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];

    // Top Selling Products
    $stmt = $conn->query("SELECT p.name, p.price, p.stock_quantity, 
                                COUNT(oi.product_id) as total_sales,
                                COALESCE(SUM(oi.quantity), 0) as units_sold
                         FROM products p
                         LEFT JOIN order_items oi ON p.product_id = oi.product_id
                         LEFT JOIN user_order o ON oi.order_id = o.order_id AND o.status != 'cancelled'
                         GROUP BY p.product_id, p.name, p.price, p.stock_quantity
                         ORDER BY units_sold DESC
                         LIMIT 5");
    $topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];

} catch (PDOException $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Error loading dashboard data'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Wattz Electronics</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --light-bg: #f8f9fa;
        }

        .dashboard-card {
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            background: white;
            height: 100%;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
        }

        .stat-card {
            padding: 1.5rem;
            text-align: center;
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #666;
            font-size: 1rem;
        }

        .table-responsive {
            border-radius: 1rem;
            overflow: hidden;
        }

        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 1rem;
            font-size: 0.8rem;
        }

        .status-pending { background-color: #ffeeba; color: #856404; }
        .status-processing { background-color: #b8daff; color: #004085; }
        .status-shipped { background-color: #c3e6cb; color: #155724; }
        .status-delivered { background-color: #d4edda; color: #155724; }
        .status-cancelled { background-color: #f5c6cb; color: #721c24; }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <h1 class="mb-4">Dashboard</h1>

        <?php if (isset($_SESSION['alert'])): ?>
            <div class="alert alert-<?php echo $_SESSION['alert']['type']; ?> alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['alert']['message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['alert']); ?>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-4 col-lg-2">
                <div class="dashboard-card stat-card">
                    <i class="fas fa-shopping-cart stat-icon text-primary"></i>
                    <div class="stat-value"><?php echo $totalOrders; ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
            </div>
            <div class="col-md-4 col-lg-2">
                <div class="dashboard-card stat-card">
                    <i class="fas fa-clock stat-icon text-warning"></i>
                    <div class="stat-value"><?php echo $pendingOrders; ?></div>
                    <div class="stat-label">Pending Orders</div>
                </div>
            </div>
            <div class="col-md-4 col-lg-2">
                <div class="dashboard-card stat-card">
                    <i class="fas fa-box stat-icon text-success"></i>
                    <div class="stat-value"><?php echo $totalProducts; ?></div>
                    <div class="stat-label">Total Products</div>
                </div>
            </div>
            <div class="col-md-4 col-lg-2">
                <div class="dashboard-card stat-card">
                    <i class="fas fa-exclamation-triangle stat-icon text-danger"></i>
                    <div class="stat-value"><?php echo $lowStockProducts; ?></div>
                    <div class="stat-label">Low Stock Items</div>
                </div>
            </div>
            <div class="col-md-4 col-lg-2">
                <div class="dashboard-card stat-card">
                    <i class="fas fa-tags stat-icon text-info"></i>
                    <div class="stat-value"><?php echo $totalCategories; ?></div>
                    <div class="stat-label">Categories</div>
                </div>
            </div>
            <div class="col-md-4 col-lg-2">
                <div class="dashboard-card stat-card">
                    <i class="fas fa-users stat-icon text-secondary"></i>
                    <div class="stat-value"><?php echo $totalCustomers; ?></div>
                    <div class="stat-label">Customers</div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Recent Orders -->
            <div class="col-lg-6">
                <div class="dashboard-card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Recent Orders</h5>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td>#<?php echo $order['order_id']; ?></td>
                                        <td><?php echo htmlspecialchars($order['username']); ?></td>
                                        <td>KSH <?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td><span class="status-badge status-<?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span></td>
                                        <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-end mt-3">
                            <a href="orders.php" class="btn btn-primary btn-sm">View All Orders</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Selling Products -->
            <div class="col-lg-6">
                <div class="dashboard-card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Top Selling Products</h5>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Units Sold</th>
                                        <th>Stock</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($topProducts as $product): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td>KSH <?php echo number_format($product['price'], 2); ?></td>
                                        <td><?php echo $product['units_sold'] ?? 0; ?></td>
                                        <td>
                                            <?php if ($product['stock_quantity'] <= 5): ?>
                                                <span class="text-danger"><?php echo $product['stock_quantity']; ?> left</span>
                                            <?php else: ?>
                                                <?php echo $product['stock_quantity']; ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-end mt-3">
                            <a href="products.php" class="btn btn-primary btn-sm">Manage Products</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
