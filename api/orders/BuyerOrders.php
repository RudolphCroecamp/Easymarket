<?php
header('Content-Type: application/json'); //return data in json format

$start = microtime(true);

require '../../config/cors.php'; //allow access from webserver
require '../../config/protectedRoute.php'; //user must be authorised
$conn = require '../../config/dbconn.php'; //connect to DB



$jsonData = json_decode(file_get_contents('php://input'), true);

// Get page number from client
$page = isset($jsonData['PAGE']) ? (int)$jsonData['PAGE'] : 1;
$limit = 10;

// Calculate offset
$offset = ($page - 1) * $limit;



$userID = $_SESSION["userID"];


$getOrdersStmt = $conn->prepare("
    SELECT
        o.orderID,
        o.totalPrice,
        o.status,
        o.created_at,
        u.fName,
        u.lName,
        u.email
    FROM orders o
    INNER JOIN users u
        ON o.sellerID = u.userID
    WHERE o.buyerID = ?
    ORDER BY o.created_at DESC
    LIMIT ? OFFSET ?
");




$getOrdersStmt->bind_param("sii", $userID,  $limit, $offset);

$getOrdersStmt->execute();

$orders_result = $getOrdersStmt->get_result();

$orders = [];

if ($orders_result && $orders_result->num_rows > 0) {
    while ($order = $orders_result->fetch_assoc()) {
        $orders[] = $order;
    }


    echo json_encode([
        "status" => "success",
        "success" => true,
        "orders" => $orders
    ]);
} else {
    echo json_encode([
        "status" => "failed",
        "success" => false,
        "error" => "No buyer orders found",
    ]);
}


//close and end connection
$getOrdersStmt->close();
$conn->close();
die();
