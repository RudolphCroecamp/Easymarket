<?php
header('Content-Type: application/json'); //return data in json format

$start = microtime(true);

require '../../config/cors.php'; //allow access from webserver
require '../../config/protectedRoute.php'; //user must be authorised
$conn = require '../../config/dbconn.php'; //connect to DB


$jsonData = json_decode(file_get_contents('php://input'), true);

$orderID = isset($jsonData['orderID']) ? (int)$jsonData['orderID'] : 0;

if(!$orderID || $orderID == 0){
    echo json_encode([
        "status" => "failed",
        "success" => false,
        "error" => "No orderID Received",
    ]);
    exit();
}


$getOrdersStmt = $conn->prepare("
    SELECT
        o.orderID,
        o.totalPrice,
        o.status,
        o.platform_fee,
        o.seller_fee,
        o.delivery_fee,
        o.created_at,

        oi.id AS order_item_id,
        oi.productID,
        oi.price,
        oi.quantity,

        p.name AS product_name

    FROM orders o
    INNER JOIN order_items oi
        ON o.orderID = oi.orderID
    INNER JOIN products p
        ON oi.productID = p.productID

    WHERE o.orderID = ?
");



$getOrdersStmt->bind_param("i", $orderID);

$getOrdersStmt->execute();

$orders_result = $getOrdersStmt->get_result();

$orders = [
    "status" => null,
    "Total" => null,
    "Date" => null,
    "platform_fee" => null,
    "seller_fee" => null,
    "delivery_fee" => null,
    "order" => []
];

if ($orders_result && $orders_result->num_rows > 0) {

    while ($order = $orders_result->fetch_assoc()) {

        // set status ONCE (same for all rows of same order)
        $orders["status"] = $order["status"];
        $orders["Total"] = $order["totalPrice"];
        $orders["Date"] = $order["created_at"];

        $orders["platform_fee"] = $order["platform_fee"];
        $orders["seller_fee"] = $order["seller_fee"];
        $orders["delivery_fee"] = $order["delivery_fee"];

        // push each item into order array
        $orders["order"][] = [
            "order_item_id" => $order["order_item_id"],
            "productID" => $order["productID"],
            "product_name" => $order["product_name"],
            "price" => $order["price"],
            "quantity" => $order["quantity"]
        ];
    }

    echo json_encode([
        "status" => "success",
        "success" => true,
        "orders" => $orders,
        "orderID" => $orderID
    ]);
        
} else {
    echo json_encode([
        "status" => "failed",
        "success" => false,
        "error" => "No orders found",
        "orderID" => $orderID
    ]);
}


//close and end connection
$getOrdersStmt->close();
$conn->close();
die();
