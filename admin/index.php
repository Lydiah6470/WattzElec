<?php
session_start();
// if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
//     header("Location: login.php");
//     exit;
// }
include 'includes/header.php';
include 'includes/db.php';

// Get dashboard statistics
function getDashboardStats($conn) {
    $stats = array();
    
    // Check if orders table exists, if not create it
    $conn->exec("CREATE TABLE IF NOT EXISTS `orders` (
        `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT(11) UNSIGNED NOT NULL,
        `total_amount` DECIMAL(10,2) NOT NULL,
        `status` ENUM('pending', 'processing', 'completed', 'cancelled', 'paid') DEFAULT 'pending',
        `payment_status` ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    // Get total sales (only paid orders)
    $salesQuery = "SELECT COALESCE(SUM(total_amount), 0) as total_sales 
                  FROM orders 
                  WHERE status != 'cancelled' 
                  AND payment_status = 'paid'";
    $stmt = $conn->query($salesQuery);
    if ($stmt === false) {
        throw new PDOException("Error fetching total sales: " . $conn->errorInfo()[2]);
    }
    $stats['total_sales'] = $stmt->fetch()['total_sales'];
    
    // Get today's sales
    $todaySalesQuery = "SELECT COALESCE(SUM(total_amount), 0) as today_sales 
                        FROM orders 
                        WHERE status != 'cancelled' 
                        AND payment_status = 'paid'
                        AND DATE(created_at) = CURDATE()";
    $stmt = $conn->query($todaySalesQuery);
    $stats['today_sales'] = $stmt->fetch()['today_sales'];
    
    // Get pending orders count
    $pendingOrdersQuery = "SELECT COUNT(*) as pending_orders 
                          FROM orders 
                          WHERE status = 'pending'";
    $stmt = $conn->query($pendingOrdersQuery);
    $stats['pending_orders'] = $stmt->fetch()['pending_orders'];
    
    // Get total orders
    $ordersQuery = "SELECT COUNT(*) as total_orders FROM orders";
    $stmt = $conn->query($ordersQuery);
    $stats['total_orders'] = $stmt->fetch()['total_orders'];
    
    // Get total users
    $usersQuery = "SELECT COUNT(*) as total_users FROM users";
    $stmt = $conn->query($usersQuery);
    $stats['total_users'] = $stmt->fetch()['total_users'];
    
    // Get low stock products (less than 10 items)
    $lowStockQuery = "SELECT COUNT(*) as low_stock_count 
                     FROM products 
                     WHERE stock < 10";
    $stmt = $conn->query($lowStockQuery);
    $stats['low_stock_count'] = $stmt->fetch()['low_stock_count'];
    
    // Get recent orders (last 5)
    $recentOrdersQuery = "SELECT o.id, o.total_amount, o.status, o.created_at, u.name as customer_name 
                         FROM orders o 
                         JOIN users u ON o.user_id = u.id 
                         ORDER BY o.created_at DESC 
                         LIMIT 5";
    $stmt = $conn->query($recentOrdersQuery);
    $stats['recent_orders'] = $stmt->fetchAll();
    
    return $stats;
}

// Fetch statistics
try {
    $stats = getDashboardStats($conn);
} catch (PDOException $e) {
    $stats = array(
        'total_sales' => 0,
        'today_sales' => 0,
        'total_orders' => 0,
        'pending_orders' => 0,
        'total_users' => 0,
        'low_stock_count' => 0,
        'recent_orders' => array()
    );
    // Log the error for admin review
    error_log("Dashboard Error: " . $e->getMessage());
}

// Helper function to format date
function formatDate($date) {
    return date('M j, Y g:i A', strtotime($date));
}
?>
<link rel="stylesheet" href="assets/css/style.css">
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

.admin-dashboard {
    padding: 2rem;
    background-color: var(--background-light);
    min-height: 100vh;
}

.main-content {
    max-width: 1400px;
    margin: 0 auto;
}

.main-content h2 {
    font-size: 2rem;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
    font-weight: 600;
}

.main-content > p {
    color: var(--text-secondary);
    margin-bottom: 2rem;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.widget {
    background: #fff;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border: 1px solid var(--border-color);
}

.widget:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 12px rgba(0, 0, 0, 0.1);
}

.widget h3 {
    margin: 0 0 1rem 0;
    color: var(--text-primary);
    font-size: 1.1rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.widget h3::before {
    content: '';
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background-color: var(--primary-color);
}

.widget p {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--primary-color);
    margin: 0.5rem 0;
}

.widget small {
    color: var(--text-secondary);
    font-size: 0.85rem;
}

.alert {
    color: #856404;
    background-color: #fff3cd;
    border: 1px solid #ffeeba;
    padding: 1rem 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    animation: slideIn 0.3s ease;
}

.alert strong {
    font-weight: 600;
}

.alert a {
    margin-left: auto;
    padding: 0.5rem 1rem;
    background-color: #856404;
    color: white;
    text-decoration: none;
    border-radius: 6px;
    font-size: 0.9rem;
    transition: background-color 0.2s ease;
}

.alert a:hover {
    background-color: #6d5204;
}

.recent-orders {
    grid-column: 1 / -1;
}

.orders-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    margin-top: 1rem;
}

.orders-table th, 
.orders-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid var(--border-color);
}

.orders-table th {
    background-color: var(--background-light);
    font-weight: 600;
    color: var(--text-primary);
}

.orders-table th:first-child {
    border-top-left-radius: 8px;
}

.orders-table th:last-child {
    border-top-right-radius: 8px;
}

.orders-table tr:last-child td:first-child {
    border-bottom-left-radius: 8px;
}

.orders-table tr:last-child td:last-child {
    border-bottom-right-radius: 8px;
}

.orders-table tbody tr {
    transition: background-color 0.2s ease;
}

.orders-table tbody tr:hover {
    background-color: var(--background-light);
}

.status-badge {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.status-badge::before {
    content: '';
    display: inline-block;
    width: 6px;
    height: 6px;
    border-radius: 50%;
}

.status-pending { 
    background-color: #fff3cd; 
    color: #856404; 
}
.status-pending::before { background-color: #856404; }

.status-processing { 
    background-color: #cce5ff; 
    color: #004085; 
}
.status-processing::before { background-color: #004085; }

.status-completed { 
    background-color: #d4edda; 
    color: #155724; 
}
.status-completed::before { background-color: #155724; }

.status-cancelled { 
    background-color: #f8d7da; 
    color: #721c24; 
}
.status-cancelled::before { background-color: #721c24; }

.status-paid { 
    background-color: #d4edda; 
    color: #155724; 
}
.status-paid::before { background-color: #155724; }

/* Stat-specific colors */
.widget.sales-total p { color: #2ecc71; }
.widget.sales-today p { color: #3498db; }
.widget.orders-total p { color: #9b59b6; }
.widget.orders-pending p { color: #f1c40f; }
.widget.users-total p { color: #1abc9c; }
.widget.stock-low p { color: #e74c3c; }

/* Responsive adjustments */
@media (max-width: 768px) {
    .admin-dashboard {
        padding: 1rem;
    }
    
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
    
    .widget p {
        font-size: 1.5rem;
    }
    
    .orders-table {
        display: block;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
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
</style>
<div class="admin-dashboard">
    <!-- Main Content -->
    <div class="main-content">
        <h2>Welcome to the Admin Dashboard</h2>
        <p>Manage your e-commerce platform from here.</p>

        <?php if ($stats['low_stock_count'] > 0): ?>
        <div class="alert">
            <strong>Low Stock Alert!</strong> <?php echo $stats['low_stock_count']; ?> products are running low on stock.
            <a href="products.php?filter=low_stock">View Products</a>
        </div>
        <?php endif; ?>

        <!-- Dashboard Widgets -->
        <div class="dashboard-grid">
            <div class="widget sales-total">
                <h3>Total Sales</h3>
                <p>KSh <?php echo number_format($stats['total_sales'], 2); ?></p>
                <small>(Paid orders only)</small>
            </div>
            <div class="widget sales-today">
                <h3>Today's Sales</h3>
                <p>KSh <?php echo number_format($stats['today_sales'], 2); ?></p>
                <small>(Paid orders only)</small>
            </div>
            <div class="widget orders-total">
                <h3>Total Orders</h3>
                <p><?php echo number_format($stats['total_orders']); ?></p>
            </div>
            <div class="widget orders-pending">
                <h3>Pending Orders</h3>
                <p><?php echo number_format($stats['pending_orders']); ?></p>
            </div>
            <div class="widget users-total">
                <h3>Total Users</h3>
                <p><?php echo number_format($stats['total_users']); ?></p>
            </div>
            <div class="widget stock-low">
                <h3>Low Stock Items</h3>
                <p><?php echo number_format($stats['low_stock_count']); ?></p>
                <?php if ($stats['low_stock_count'] > 0): ?>
                <small class="text-danger">Needs attention!</small>
                <?php endif; ?>
            </div>

            <!-- Recent Orders Section -->
            <div class="widget recent-orders">
                <h3>Recent Orders</h3>
                <?php if (!empty($stats['recent_orders'])): ?>
                <table class="orders-table">
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
                        <?php foreach ($stats['recent_orders'] as $order): ?>
                        <tr>
                            <td>#<?php echo $order['id']; ?></td>
                            <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                            <td>KSh <?php echo number_format($order['total_amount'], 2); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <td><?php echo formatDate($order['created_at']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p>No recent orders found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>