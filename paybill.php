<?php include 'includes/header.php'; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Summary</title>
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
</head>

<body>

    <?php
    // session_start();
    include 'includes/db.php';

    // Get order ID from URL or session
    $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : (isset($_SESSION['order_id']) ? intval($_SESSION['order_id']) : 0);
    if ($order_id === 0) {
        echo "<p class='text-center text-danger'>Invalid order ID.</p>";
        exit;
    }

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }

    // Fetch user ID based on username
    $userQuery = "SELECT id FROM users WHERE id = ?";
    $userStmt = $conn->prepare($userQuery);
    $userStmt->execute([$user_id]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "<p class='text-center text-danger'>User not found.</p>";
        exit;
    }
    $user_id = $user['id'];

    // Fetch order details
    $sql = "SELECT o.id AS order_id, o.total_amount, o.payment_method, o.status, o.created_at, u.email 
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE o.id = ? AND o.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo "<p class='text-center text-danger'>Order not found or does not belong to this user.</p>";
        exit;
    }

    // Extract order details
    $email = $order['email'] ?? '';
    $order_date = $order['created_at'] ?? '1970-01-01';
    $total_amount = (float)($order['total_amount'] ?? 0);
    $payment_method = $order['payment_method'] ?? 'N/A';
    $status = $order['status'] ?? 'pending';
    ?>

    <div class="container jumbotron">
        <h1 class="text-center" style="color: green;">Payment Summary</h1>
    </div>

    <h3 class="text-center">
        <strong>Order Number:</strong> 
        <span style="color: blue;"><?php echo htmlspecialchars((string)$order_id); ?></span>
    </h3>

    <div class="container">
        <div class="box">
            <div class="col-md-10" style="margin: 0 auto; text-align: center;">
                <h3 style="color: orange;">Invoice</h3>
            </div>
            <div class="col-md-10" style="margin: 0 auto;">
                <h4> <strong>Order Date:</strong> <?php echo htmlspecialchars(date("Y-m-d", strtotime($order_date))); ?> </h4>
                <br>
                <h4> <strong>Payment Method:</strong> <?php echo htmlspecialchars($payment_method); ?> </h4>
                <br>
                <h4> <strong>Total Amount:</strong> Ksh <?php echo number_format($total_amount, 2); ?>/- </h4>
                <br>
                <h4> <strong>Status:</strong> 
                    <span class="status-<?php echo strtolower($status); ?>">
                        <?php echo htmlspecialchars(ucfirst($status)); ?>
                    </span>
                </h4>
            </div>

            <form action="paidsuccess.php" method="POST">
                <input type="hidden" name="amount" value="<?php echo htmlspecialchars((string)$total_amount); ?>">
                <input type="hidden" name="order_id" value="<?php echo htmlspecialchars((string)$order_id); ?>">

                <div class="text-center">
                    <script src="https://checkout.stripe.com/checkout.js" class="stripe-button"
                        data-key="pk_test_51JWFHASCAKEV732YxR0Zhagt9mVlFMgvpqf9zlDwAi94U6VvfATE0zkTp4NSj9kxklSmYuyyDLY0ovzqrDL0hbns00OG1mXtJo"
                        data-amount="<?php echo str_replace(",", "", $total_amount) * 100; ?>"
                        data-name="Wattz Electronics"
                        data-description="Order ID: <?php echo htmlspecialchars((string)$order_id); ?>"
                        data-image="images/favicon.png"
                        data-email="<?php echo htmlspecialchars($email); ?>"
                        data-currency="KES"
                        data-locale="auto">
                    </script>
                </div>
            </form><br><br>

            <div class="text-center">
                <a class="badge1 badge1-edit" href="printbillpdf.php?order_id=<?php echo htmlspecialchars((string)$order_id); ?>"> Download PDF</a>
                <a class="badge1 badge1-delete" href="index.php"> Back to Home</a>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>

</html>