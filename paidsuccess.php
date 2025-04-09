<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$amount = $_POST["amount"];
$car_name = $_POST['car_name'];
$id = $_POST['order_id'];

header("Location:paysuccessmsg.php?order_id=$id");
