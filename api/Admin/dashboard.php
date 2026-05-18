<?php
header('Content-Type: application/json');

require '../../config/cors.php';//allow access from webserver

// require '../../config/adminOnly.php';//user must be authorised
$conn = require '../../config/dbconn.php';//connect to DB

// ----------------------
// 1. TOTAL GMV
// ----------------------
$gmvQuery = $conn->query("
    SELECT SUM(price) as gmv 
    FROM orders 
    WHERE status = 'Awaiting Payment'
");
$gmv = $gmvQuery->fetch_assoc()['gmv'] ?? 0;


// ----------------------
// 2. TOTAL ORDERS
// ----------------------
$orderQuery = $conn->query("
    SELECT COUNT(*) as orders 
    FROM orders
");
$orders = $orderQuery->fetch_assoc()['orders'];


// ----------------------
// 3. ACTIVE USERS (last 30 days)
// ----------------------
$userQuery = $conn->query("
    SELECT COUNT(DISTINCT buyerID) as active_users 
    FROM orders 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
");
$activeUsers = $userQuery->fetch_assoc()['active_users'];


// ----------------------
// 4. GMV TREND (last 7 days)
// ----------------------
$trendQuery = $conn->query("
    SELECT 
        DATE(created_at) as day,
        SUM(price) as total
    FROM orders
    WHERE status = 'Awaiting Payment'
    AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY day ASC
");

$labels = [];
$data = [];

while ($row = $trendQuery->fetch_assoc()) {
    $labels[] = $row['day'];
    $data[] = (float)$row['total'];
}



// ----------------------
// 5. Retention
// ----------------------
$sellerRetentionQuery = $conn->query("
    SELECT COUNT(*) AS repeat_sellers
    FROM (
        SELECT ownerID
        FROM products
        GROUP BY ownerID
        HAVING COUNT(*) > 1
    ) AS seller_counts;
 ");

$sellerRetention = $sellerRetentionQuery->fetch_assoc()['repeat_sellers'] ?? 0;

$buyerRetentionQuery = $conn->query("
    SELECT COUNT(*) AS repeat_buyers
    FROM (
        SELECT buyerID
        FROM orders
        WHERE status = 'Completed'
        GROUP BY buyerID
        HAVING COUNT(*) > 1
    ) AS buyer_counts;
");

$buyerRetention = $buyerRetentionQuery->fetch_assoc()['repeat_buyers'] ?? 0;









// ----------------------
// OUTPUT JSON
// ----------------------
echo json_encode([
    "gmv" => (float)$gmv,
    "orders" => (int)$orders,
    "activeUsers" => (int)$activeUsers,
    "gmvTrend" => [
        "labels" => $labels,
        "data" => $data
    ],
    "sellerRetention" => $sellerRetention,
    "buyerRetention" => $buyerRetention,
]);