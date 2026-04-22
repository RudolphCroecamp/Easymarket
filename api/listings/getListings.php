<?php
    header('Content-Type: application/json');

    //catch and return errors
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    require '../../config/cors.php';//allow access from webserver
    require '../../config/protectedRoute.php';//user must be authorised
    require '../../config/dbconn.php';//connect to DB

    // Get page number from client
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 20;

    // Calculate offset
    $offset = ($page - 1) * $limit;


    $filter = $_GET["filterListings"] ?? 'available';

    if ($filter === 'all') {
        $sql = "SELECT * FROM products 
                WHERE ownerID = ? 
                ORDER BY deleted ASC, created_at DESC 
                LIMIT ? OFFSET ?";
    }
    elseif ($filter === 'deleted') {
        $sql = "SELECT * FROM products 
                WHERE ownerID = ? AND deleted = TRUE 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?";
    }
    else {
        $sql = "SELECT * FROM products 
                WHERE ownerID = ? AND deleted = FALSE 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?";
    }

    
    //get userID from session
    $userID = $_SESSION['userID'];

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $userID, $limit, $offset);
    $stmt->execute();

    //get data from db
    $result = $stmt->get_result();

    //check if we have received rows form the db
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }

        //return products in json format
        echo json_encode([
            "status"=>"success",
            "success"=>true,
            "products"=>$products
        ]);

    }else{
        //return a failed message when no data was return from the db
        echo json_encode([
            "status"=>"failed",
            "success"=>false,
            "error" => "No listings found"
        ]);
    }


    
    //close and end connection
    $stmt->close();
    $conn->close();
    die();
