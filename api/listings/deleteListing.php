
<?php
    header('Content-Type: application/json');

    //catch and return errors
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    require '../../config/cors.php';
    require '../../config/protectedRoute.php';
    require '../../config/dbconn.php';

    $data = json_decode(file_get_contents("php://input"), true);

    // Validate input
    if (!isset($data['productID'])) {
        echo json_encode([
            "status" => "failed",
            "success" => false,
            "error" => "No productID received"
        ]);
        exit;
    }

    $productID = $data['productID'];
    $userID = $_SESSION['userID'];

    // Delete (soft delete)
    $sql = "UPDATE products SET deleted = true WHERE productID = ? AND ownerID = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $productID, $userID);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode([
            "status" => "success",
            "success" => true,
            "message" => "Listing deleted"
        ]);
    } else {
        echo json_encode([
            "status" => "failed",
            "success" => false,
            "error" => "Could not delete listing"
        ]);
    }

    //close and end connection
    $stmt->close();
    $conn->close();
    die();
