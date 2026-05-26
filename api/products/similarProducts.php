<?php
header('Content-Type: application/json');

require '../../config/cors.php';
require '../../config/protectedRoute.php';
$conn = require '../../config/dbconn.php';

$limit = 12;

if (!isset($_POST["productID"])) {
    echo json_encode([
        "status" => "failed",
        "success" => false,
        "error" => "No productID present",
    ]);
    exit;
}

$productID = $_POST["productID"];
$products = [];

$sql = 
"
    SELECT *
    FROM products
    WHERE deleted = FALSE
    AND category = (
        SELECT category
        FROM products
        WHERE productID = ?
        LIMIT 1
    )
    AND productID != ?
    ORDER BY name ASC
    LIMIT 12
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $productID, $productID);

$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }

    echo json_encode([
        "status" => "success",
        "success" => true,
        "products" => $products
    ]);
} else {
    echo json_encode([
        "status" => "failed",
        "success" => false,
        "error" => "No products found"
    ]);
}

$stmt->close();
$conn->close();
exit;