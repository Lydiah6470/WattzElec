<?php
ob_start();
session_start();
include 'includes/db.php';

// Get date range and report type from request
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'sales';

// Base WHERE clause for date filtering
$date_filter = "WHERE o.created_at BETWEEN :start_date AND DATE_ADD(:end_date, INTERVAL 1 DAY)";

// Initialize variables
$metrics = $topProducts = $lowStockProducts = $customerMetrics = $dailyRevenue = [];

// Handle PDF Report Generation first, before any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_report'])) {
    // Get the posted dates and report type
    $pdf_start_date = $_POST['start_date'];
    $pdf_end_date = $_POST['end_date'];
    $pdf_report_type = $_POST['report_type'];
    
    // Fetch data for PDF based on report type
    if ($pdf_report_type === 'sales' || $pdf_report_type === 'all') {
        $metricsQuery = "
            SELECT 
                COUNT(*) AS total_sales,
                SUM(CASE WHEN o.status = 'delivered' THEN 1 ELSE 0 END) AS total_delivered,
                SUM(CASE WHEN o.status = 'delivered' THEN o.total_amount ELSE 0 END) AS total_revenue
            FROM orders o 
            WHERE o.created_at BETWEEN :start_date AND DATE_ADD(:end_date, INTERVAL 1 DAY)
        ";
        $metricsStmt = $conn->prepare($metricsQuery);
        $metricsStmt->execute(['start_date' => $pdf_start_date, 'end_date' => $pdf_end_date]);
        $pdfMetrics = $metricsStmt->fetch(PDO::FETCH_ASSOC);
    }

    if ($pdf_report_type === 'inventory' || $pdf_report_type === 'all') {
        $topProductsQuery = "
            SELECT 
                p.name, 
                p.stock,
                SUM(oi.quantity) AS total_quantity,
                SUM(oi.quantity * oi.price) AS total_revenue
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            JOIN orders o ON oi.order_id = o.id
            WHERE o.created_at BETWEEN :start_date AND DATE_ADD(:end_date, INTERVAL 1 DAY)
            GROUP BY p.id
            ORDER BY total_quantity DESC
            LIMIT 10
        ";
        $topProductsStmt = $conn->prepare($topProductsQuery);
        $topProductsStmt->execute(['start_date' => $pdf_start_date, 'end_date' => $pdf_end_date]);
        $pdfTopProducts = $topProductsStmt->fetchAll(PDO::FETCH_ASSOC);

        $lowStockQuery = "
            SELECT name, stock, price
            FROM products
            WHERE stock <= 10
            ORDER BY stock ASC
            LIMIT 10
        ";
        $lowStockStmt = $conn->prepare($lowStockQuery);
        $lowStockStmt->execute();
        $pdfLowStock = $lowStockStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    if ($pdf_report_type === 'customers' || $pdf_report_type === 'all') {
        $customerMetricsQuery = "
            SELECT 
                COUNT(DISTINCT o.user_id) as total_customers,
                ROUND(AVG(o.total_amount), 2) as avg_order_value,
                COUNT(o.id) / COUNT(DISTINCT o.user_id) as orders_per_customer
            FROM orders o
            WHERE o.created_at BETWEEN :start_date AND DATE_ADD(:end_date, INTERVAL 1 DAY)
        ";
        $customerMetricsStmt = $conn->prepare($customerMetricsQuery);
        $customerMetricsStmt->execute(['start_date' => $pdf_start_date, 'end_date' => $pdf_end_date]);
        $pdfCustomerMetrics = $customerMetricsStmt->fetch(PDO::FETCH_ASSOC);
    }

    // Generate PDF
    require_once '../vendor/autoload.php';

    class PDF extends FPDF {
        function Header() {
            $this->SetFont('Arial', 'B', 16);
            $this->Cell(0, 10, 'WattzElec Analytics Report', 0, 1, 'C');
            $this->Ln(5);
        }

        function Footer() {
            $this->SetY(-15);
            $this->SetFont('Arial', 'I', 8);
            $this->Cell(0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C');
        }

        function ChapterTitle($title) {
            $this->SetFont('Arial', 'B', 14);
            $this->Cell(0, 10, $title, 0, 1, 'L');
            $this->Ln(5);
        }

        function ChapterBody($body) {
            $this->SetFont('Arial', '', 11);
            $this->MultiCell(0, 5, $body);
            $this->Ln(5);
        }
    }

    // Create PDF instance
    $pdf = new PDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', '', 11);

    // Report Period
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 10, "Period: $pdf_start_date to $pdf_end_date", 0, 1, 'C');
    $pdf->Ln(10);

    if ($pdf_report_type === 'sales' || $pdf_report_type === 'all') {
        $pdf->ChapterTitle('Sales Overview');
        if (isset($pdfMetrics['total_sales'])) {
            $pdf->Cell(0, 8, 'Total Sales: ' . number_format($pdfMetrics['total_sales']), 0, 1);
            $pdf->Cell(0, 8, 'Delivered Orders: ' . number_format($pdfMetrics['total_delivered']), 0, 1);
            $pdf->Cell(0, 8, 'Total Revenue: KSH ' . number_format($pdfMetrics['total_revenue'], 2), 0, 1);
        }
        $pdf->Ln(5);
    }

    if ($pdf_report_type === 'inventory' || $pdf_report_type === 'all') {
        $pdf->ChapterTitle('Top-Selling Products');
        if (!empty($pdfTopProducts)) {
            // Table header
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(80, 8, 'Product', 1);
            $pdf->Cell(30, 8, 'Quantity', 1);
            $pdf->Cell(40, 8, 'Revenue', 1);
            $pdf->Cell(30, 8, 'Stock', 1);
            $pdf->Ln();

            // Table data
            $pdf->SetFont('Arial', '', 11);
            foreach ($pdfTopProducts as $product) {
                $pdf->Cell(80, 8, $product['name'], 1);
                $pdf->Cell(30, 8, number_format($product['total_quantity']), 1);
                $pdf->Cell(40, 8, 'KSH ' . number_format($product['total_revenue'], 2), 1);
                $pdf->Cell(30, 8, number_format($product['stock']), 1);
                $pdf->Ln();
            }
        }
        $pdf->Ln(10);

        $pdf->ChapterTitle('Low Stock Alert');
        if (!empty($pdfLowStock)) {
            // Table header
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(100, 8, 'Product', 1);
            $pdf->Cell(40, 8, 'Stock', 1);
            $pdf->Cell(40, 8, 'Price', 1);
            $pdf->Ln();

            // Table data
            $pdf->SetFont('Arial', '', 11);
            foreach ($pdfLowStock as $product) {
                $pdf->Cell(100, 8, $product['name'], 1);
                $pdf->Cell(40, 8, number_format($product['stock']), 1);
                $pdf->Cell(40, 8, 'KSH ' . number_format($product['price'], 2), 1);
                $pdf->Ln();
            }
        }
        $pdf->Ln(5);
    }

    if ($pdf_report_type === 'customers' || $pdf_report_type === 'all') {
        $pdf->ChapterTitle('Customer Overview');
        if (isset($pdfCustomerMetrics['total_customers'])) {
            $pdf->Cell(0, 8, 'Total Customers: ' . number_format($pdfCustomerMetrics['total_customers']), 0, 1);
            $pdf->Cell(0, 8, 'Average Order Value: KSH ' . number_format($pdfCustomerMetrics['avg_order_value'], 2), 0, 1);
            $pdf->Cell(0, 8, 'Orders per Customer: ' . number_format($pdfCustomerMetrics['orders_per_customer'], 1), 0, 1);
        }
    }

    // Clear any output buffers
    ob_end_clean();

    // Output PDF
    $pdf->Output('WattzElec_Analytics_Report.pdf', 'D');
    exit;
}

// Include header after PDF generation
include 'includes/header.php';

// Get date range and report type from request
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'sales';

// Base WHERE clause for date filtering
$date_filter = "WHERE o.created_at BETWEEN :start_date AND DATE_ADD(:end_date, INTERVAL 1 DAY)";

// Initialize variables
$metrics = $topProducts = $lowStockProducts = $customerMetrics = $dailyRevenue = [];

// Fetch data based on report type
if ($report_type === 'sales' || $report_type === 'all') {
    // Sales Metrics
    $metricsQuery = "
        SELECT 
            COUNT(*) AS total_sales,
            SUM(CASE WHEN o.status = 'delivered' THEN 1 ELSE 0 END) AS total_delivered,
            SUM(CASE WHEN o.status = 'delivered' THEN o.total_amount ELSE 0 END) AS total_revenue
        FROM orders o 
        $date_filter
    ";
    $metricsStmt = $conn->prepare($metricsQuery);
    $metricsStmt->execute(['start_date' => $start_date, 'end_date' => $end_date]);
    $metrics = $metricsStmt->fetch(PDO::FETCH_ASSOC);

    // Daily Revenue Data
    $dailyRevenueQuery = "
        SELECT 
            DATE(o.created_at) as date,
            COUNT(*) as total_orders,
            SUM(o.total_amount) as revenue
        FROM orders o
        $date_filter
        GROUP BY DATE(o.created_at)
        ORDER BY date ASC
    ";
    $dailyRevenueStmt = $conn->prepare($dailyRevenueQuery);
    $dailyRevenueStmt->execute(['start_date' => $start_date, 'end_date' => $end_date]);
    $dailyRevenue = $dailyRevenueStmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($report_type === 'inventory' || $report_type === 'all') {
    // Top Products
    $topProductsQuery = "
        SELECT 
            p.name, 
            p.stock,
            SUM(oi.quantity) AS total_quantity,
            SUM(oi.quantity * oi.price) AS total_revenue
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        JOIN orders o ON oi.order_id = o.id
        $date_filter
        GROUP BY p.id
        ORDER BY total_quantity DESC
        LIMIT 10
    ";
    $topProductsStmt = $conn->prepare($topProductsQuery);
    $topProductsStmt->execute(['start_date' => $start_date, 'end_date' => $end_date]);
    $topProducts = $topProductsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Low Stock Products
    $lowStockQuery = "
        SELECT name, stock, price
        FROM products
        WHERE stock <= 10
        ORDER BY stock ASC
        LIMIT 10
    ";
    $lowStockStmt = $conn->prepare($lowStockQuery);
    $lowStockStmt->execute();
    $lowStockProducts = $lowStockStmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($report_type === 'customers' || $report_type === 'all') {
    // Customer Metrics
    $customerMetricsQuery = "
        SELECT 
            COUNT(DISTINCT o.user_id) as total_customers,
            ROUND(AVG(o.total_amount), 2) as avg_order_value,
            COUNT(o.id) / COUNT(DISTINCT o.user_id) as orders_per_customer
        FROM orders o
        $date_filter
    ";
    $customerMetricsStmt = $conn->prepare($customerMetricsQuery);
    $customerMetricsStmt->execute(['start_date' => $start_date, 'end_date' => $end_date]);
    $customerMetrics = $customerMetricsStmt->fetch(PDO::FETCH_ASSOC);
}

?>

<style>
.analytics-container {
    padding: 2rem;
    max-width: 1400px;
    margin: 0 auto;
}

.date-filter {
    background: white;
    padding: 1.5rem;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.report-section {
    background: white;
    padding: 1.5rem;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
    transition: all 0.3s ease;
}

.report-section:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.report-section h4 {
    color: #2c3e50;
    font-size: 1.25rem;
    margin-bottom: 1.5rem;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid #e9ecef;
}

.metric-card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(67,97,238,0.1);
    transition: all 0.3s ease;
    height: 100%;
    border: 1px solid rgba(67,97,238,0.1);
}

.metric-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 8px rgba(67,97,238,0.2);
}

.metric-card .card-body {
    padding: 1.5rem;
}

.metric-card .card-title {
    color: #6c757d;
    font-size: 0.9rem;
    font-weight: 500;
    margin-bottom: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.metric-value {
    color: #2c3e50;
    font-size: 1.75rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.metric-label {
    color: #6c757d;
    font-size: 0.85rem;
    margin-bottom: 0;
}

.analytics-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.analytics-table th {
    background: #f8f9fa;
    color: #2c3e50;
    font-weight: 600;
    padding: 1rem;
    text-align: left;
    border-bottom: 2px solid #e9ecef;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.analytics-table td {
    padding: 1rem;
    border-bottom: 1px solid #e9ecef;
    color: #2c3e50;
    font-size: 0.95rem;
}

.analytics-table tbody tr:hover {
    background-color: #f8f9fa;
}

.stock-warning {
    color: #e74c3c;
    font-weight: 500;
}

.chart-container {
    background: white;
    padding: 1.5rem;
    border-radius: 10px;
    margin-top: 1.5rem;
}

.form-select, .form-control {
    border: 1px solid #e9ecef;
    padding: 0.75rem;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.form-select:focus, .form-control:focus {
    border-color: #4361ee;
    box-shadow: 0 0 0 0.2rem rgba(67,97,238,0.25);
}

.form-label {
    color: #2c3e50;
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.btn-primary {
    background: #4361ee;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background: #2c3e50;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(44,62,80,0.2);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .analytics-container {
        padding: 1rem;
    }
    
    .metric-card .metric-value {
        font-size: 1.5rem;
    }
    
    .table-responsive {
        margin: 0 -1rem;
        padding: 0 1rem;
        width: calc(100% + 2rem);
    }
    
    .analytics-table th, .analytics-table td {
        padding: 0.75rem;
        font-size: 0.85rem;
    }
}

/* Animation for metrics */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.metric-card {
    animation: fadeInUp 0.5s ease forwards;
}

.metric-card:nth-child(2) {
    animation-delay: 0.1s;
}

.metric-card:nth-child(3) {
    animation-delay: 0.2s;
}

/* Custom scrollbar */
.table-responsive::-webkit-scrollbar {
    height: 6px;
}

.table-responsive::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.table-responsive::-webkit-scrollbar-thumb {
    background: #4361ee;
    border-radius: 3px;
}

.table-responsive::-webkit-scrollbar-thumb:hover {
    background: #2c3e50;
}
</style>

<div class="analytics-container">
    <h2 class="text-center mb-4">Analytics Dashboard</h2>

    <!-- Report Type and Date Range Filter -->
    <div class="date-filter">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="report_type" class="form-label">Report Type</label>
                <select class="form-select" id="report_type" name="report_type">
                    <option value="all" <?php echo $report_type === 'all' ? 'selected' : ''; ?>>All Reports</option>
                    <option value="sales" <?php echo $report_type === 'sales' ? 'selected' : ''; ?>>Sales Report</option>
                    <option value="inventory" <?php echo $report_type === 'inventory' ? 'selected' : ''; ?>>Inventory Report</option>
                    <option value="customers" <?php echo $report_type === 'customers' ? 'selected' : ''; ?>>Customer Report</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" class="form-control" id="start_date" name="start_date" 
                       value="<?php echo htmlspecialchars($start_date); ?>" max="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="col-md-3">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" class="form-control" id="end_date" name="end_date" 
                       value="<?php echo htmlspecialchars($end_date); ?>" max="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-sync-alt me-2"></i>Generate Report
                </button>
            </div>
        </form>
    </div>

    <?php if ($report_type === 'sales' || $report_type === 'all'): ?>
    <!-- Sales Metrics -->
    <div class="report-section">
        <h4><i class="fas fa-chart-line me-2"></i>Sales Overview</h4>
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="metric-card">
                    <div class="card-body text-center">
                        <h5 class="card-title">Total Sales</h5>
                        <p class="metric-value"><?php echo number_format($metrics['total_sales']); ?></p>
                        <p class="metric-label">Orders</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="metric-card">
                    <div class="card-body text-center">
                        <h5 class="card-title">Delivered Orders</h5>
                        <p class="metric-value"><?php echo number_format($metrics['total_delivered']); ?></p>
                        <p class="metric-label">Completed</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="metric-card">
                    <div class="card-body text-center">
                        <h5 class="card-title">Total Revenue</h5>
                        <p class="metric-value">KSH <?php echo number_format($metrics['total_revenue'], 2); ?></p>
                        <p class="metric-label">In sales</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sales Chart -->
        <div class="chart-container">
            <canvas id="salesChart" height="100"></canvas>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($report_type === 'inventory' || $report_type === 'all'): ?>
    <!-- Inventory Report -->
    <div class="row">
        <div class="col-md-6">
            <div class="report-section">
                <h4><i class="fas fa-box me-2"></i>Top-Selling Products</h4>
                <div class="table-responsive">
                    <table class="analytics-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Revenue</th>
                                <th>Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topProducts as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo number_format($product['total_quantity']); ?></td>
                                <td>KSH <?php echo number_format($product['total_revenue'], 2); ?></td>
                                <td><?php echo number_format($product['stock']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="report-section">
                <h4><i class="fas fa-exclamation-triangle me-2"></i>Low Stock Alert</h4>
                <div class="table-responsive">
                    <table class="analytics-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Stock</th>
                                <th>Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lowStockProducts as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td class="stock-warning"><?php echo number_format($product['stock']); ?></td>
                                <td>KSH <?php echo number_format($product['price'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($report_type === 'customers' || $report_type === 'all'): ?>
    <!-- Customer Metrics -->
    <div class="report-section">
        <h4><i class="fas fa-users me-2"></i>Customer Overview</h4>
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="metric-card">
                    <div class="card-body text-center">
                        <h5 class="card-title">Total Customers</h5>
                        <p class="metric-value"><?php echo number_format($customerMetrics['total_customers']); ?></p>
                        <p class="metric-label">Unique buyers</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="metric-card">
                    <div class="card-body text-center">
                        <h5 class="card-title">Average Order Value</h5>
                        <p class="metric-value">KSH <?php echo number_format($customerMetrics['avg_order_value'], 2); ?></p>
                        <p class="metric-label">Per order</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="metric-card">
                    <div class="card-body text-center">
                        <h5 class="card-title">Orders per Customer</h5>
                        <p class="metric-value"><?php echo number_format($customerMetrics['orders_per_customer'], 1); ?></p>
                        <p class="metric-label">Average</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Generate PDF Report Button -->
    <div class="text-center mt-4">
        <form method="POST" style="display: inline;">
            <input type="hidden" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
            <input type="hidden" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
            <input type="hidden" name="report_type" value="<?php echo htmlspecialchars($report_type); ?>">
            <button type="submit" name="generate_report" class="btn btn-primary">
                <i class="fas fa-file-pdf me-2"></i>Download PDF Report
            </button>
        </form>
    </div>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Date range validation
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');

    startDate.addEventListener('change', function() {
        endDate.min = this.value;
    });

    endDate.addEventListener('change', function() {
        startDate.max = this.value;
    });

    <?php if ($report_type === 'sales' || $report_type === 'all'): ?>
    // Sales Chart
    const dailyData = <?php echo json_encode($dailyRevenue); ?>;
    const dates = dailyData.map(item => item.date);
    const revenues = dailyData.map(item => parseFloat(item.revenue));
    const orders = dailyData.map(item => parseInt(item.total_orders));

    new Chart(document.getElementById('salesChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: dates,
            datasets: [{
                label: 'Revenue (KSH)',
                data: revenues,
                borderColor: '#4361ee',
                backgroundColor: 'rgba(67,97,238,0.1)',
                yAxisID: 'y',
                fill: true
            }, {
                label: 'Orders',
                data: orders,
                borderColor: '#2ecc71',
                backgroundColor: 'rgba(46,204,113,0.1)',
                yAxisID: 'y1',
                fill: true
            }]
        },
        options: {
            responsive: true,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Daily Sales & Revenue Trends'
                }
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Revenue (KSH)'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Number of Orders'
                    },
                    grid: {
                        drawOnChartArea: false
                    }
                }
            }
        }
    });
    <?php endif; ?>
});
</script>

<?php
ob_end_flush();
include 'includes/footer.php';
?>