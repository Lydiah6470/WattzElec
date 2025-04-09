<?php
session_start();
include 'includes/db.php';

if (isset($_GET["order_id"]) && !empty($_GET["order_id"])) {
    $id = intval($_GET['order_id']);

    // Update the order status to "paid"
    $sql = "UPDATE orders SET payment_status='paid' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);

    if ($stmt->rowCount() === 0) {
        echo "<p class='text-center text-danger'>Order not found or already processed.</p>";
        exit;
    }
?>

<html>

<head>
    <link href="https://fonts.googleapis.com/css?family=Nunito+Sans:400,400i,700,900&display=swap" rel="stylesheet">
</head>
<style>
    .bdy {
        text-align: center;
        padding: 40px 0;
        background: #EBF0F5;
    }

    .head1 {
        color: #88B04B;
        font-family: "Nunito Sans", "Helvetica Neue", sans-serif;
        font-weight: 900;
        font-size: 40px;
        margin-bottom: 10px;
    }

    .para {
        color: #404F5E;
        font-family: "Nunito Sans", "Helvetica Neue", sans-serif;
        font-size: 20px;
        margin: 0;
    }

    .checkmark {
        color: #9ABC66;
        font-size: 200px;
        line-height: 200px;
        margin-left: -15px;
    }

    .card {
        margin: 10px;
        background: white;
        padding: 60px;
        border-radius: 4px;
        box-shadow: 0 2px 3px #C8D0D8;
        display: inline-block;
        margin: 0 auto;
    }

    .badge1-delete {
        background: #9ABC66;
    }

    .badge1-delete:hover {
        background-color: rgb(134, 0, 0);
        color: #fff;
        text-decoration: none;
    }

    .badge1 {
        color: #fff;
        padding: 10px 20px;
        text-transform: uppercase;
        font-weight: 500;
        margin-bottom: 40px;
        border-radius: 30px;
    }
</style>

<body>
    <div class="bdy">
        <div class="card">
            <div style="border-radius:200px; height:200px; width:200px; margin:0 auto;">
                <i class="checkmark">✓</i>
            </div>
            <h1 class="head1">Transaction Successful</h1>
            <p class="para">We received your payment;<br /> thank you for shopping with us!</p><br>
            <a href="index.php" class="badge1 badge1-delete">Back to Home</a>
        </div>
    </div>
</body>

</html>

<?php
    // Fetch order details for the email
    $sql1 = "SELECT o.id AS order_id, o.total_amount, o.payment_method, o.created_at, u.email, u.name 
             FROM orders o
             JOIN users u ON o.user_id = u.id
             WHERE o.id = ?";
    $stmt1 = $conn->prepare($sql1);
    $stmt1->execute([$id]);
    $row = $stmt1->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo "<p class='text-center text-danger'>Order details not found.</p>";
        exit;
    }

    $email = $row['email'];
    $name = $row['username'];
    $total_amount = $row['total_amount'];
    $payment_method = $row['payment_method'];
    $order_date = $row['created_at'];

    // Fetch order items
    $itemsQuery = "SELECT p.name AS product_name, oi.quantity, oi.price 
                   FROM order_items oi
                   JOIN products p ON oi.product_id = p.id
                   WHERE oi.order_id = ?";
    $itemsStmt = $conn->prepare($itemsQuery);
    $itemsStmt->execute([$id]);
    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Build the email content
    $html = "<h1>Thank You, $name!</h1>
             <h2>Your Order Has Been Successfully Processed</h2>
             <p>Order ID: $id</p>
             <p>Order Date: $order_date</p>
             <p>Payment Method: $payment_method</p>
             <p>Total Amount: Ksh. $total_amount</p>
             <h3>Order Details:</h3>
             <table style='border:2px solid black; border-collapse: collapse;'>
                 <tr style='background-color: #f2f2f2;'>
                     <th style='border: 1px solid black; padding: 10px;'>Product</th>
                     <th style='border: 1px solid black; padding: 10px;'>Quantity</th>
                     <th style='border: 1px solid black; padding: 10px;'>Price</th>
                 </tr>";

    foreach ($items as $item) {
        $html .= "<tr>
                     <td style='border: 1px solid black; padding: 10px;'>" . htmlspecialchars($item['product_name']) . "</td>
                     <td style='border: 1px solid black; padding: 10px;'>" . htmlspecialchars($item['quantity']) . "</td>
                     <td style='border: 1px solid black; padding: 10px;'>Ksh. " . number_format($item['price'], 2) . "</td>
                  </tr>";
    }

    $html .= "</table>";

    // Send the email
    sendingmail('Order Confirmation', $html, $email, '');

    include 'footer.inc.php';
}
?>