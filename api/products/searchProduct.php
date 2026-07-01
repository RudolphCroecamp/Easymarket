<?php
    header('Content-Type: application/json');

    require '../../config/cors.php';//allow access from webserver
    require '../../config/protectedRoute.php';//user must be authorised
    $conn = require '../../config/dbconn.php';//connect to DB

    $minQueryLength = 0;
    $maxQueryLength = 32;

    //get search query from client
    if(isset($_GET['query'])){
        $query = (string)$_GET['query'];
        $query= trim($query); //remove unwanted spaces

        //check that the query is an apropriate length
        if (strlen($query) <= $minQueryLength || strlen($query) >= $maxQueryLength) {
            //close and end connection and return error message
            exit(json_encode([
                "status"=>"failed",
                "success"=>false,
                "error" => "Invalid search term length"
            ]));
        }

    }else{
        //close and end connection and return error message
        exit(json_encode([
            "status"=>"failed",
            "success"=>false,
            "error" => "Search term required"
        ]));

    }


    // Get page number from client
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 20;

    if(!is_numeric($page) || $page < 1){
        $errors[] = "Invalid page number";
    }

    // Calculate offset
    $offset = ($page - 1) * $limit;

    // SQL to match name, category, or any tag
    $sql = "
        SELECT DISTINCT p.*, u.fName, u.lName
        FROM products p
        LEFT JOIN users u ON p.ownerID = u.userID
        LEFT JOIN product_tags pt ON p.productID = pt.productID
        LEFT JOIN tags t ON pt.tagID = t.tagID
        WHERE p.name LIKE ?
        OR p.category LIKE ?
        OR t.name LIKE ?
        ORDER BY p.name ASC
    ";
    // Use wildcards for LIKE
    $likeQuery = "%$query%";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $likeQuery, $likeQuery, $likeQuery);

    $stmt->execute();
    $result = $stmt->get_result();

    //check if we have received rows form the db
    if ($result->num_rows > 0) {
        $products = $result->fetch_all(MYSQLI_ASSOC);

        //close and end connection and return products in json format
        exit(json_encode([
            "status"=>"success",
            "success"=>true,
            "products"=>$products
        ]));

    }else{
        //close and end connection and return a failed message when no data was return from the db
        exit(json_encode([
            "status"=>"failed",
            "success"=>false,
            "error" => "No products found"
        ]));
    }
    


    // //edit query so we can find matching words
    // $search = "%".$query."%";

    // //prepare sql command
    
    // $stmt->bind_param("ssssii", $search, $search, $search, $search, $limit, $offset);
    // $stmt->execute();

    // //get data from db
    // $result = $stmt->get_result();

    //check if we have received rows form the db
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }

        //close and end connection and return products in json format
        exit(json_encode([
            "status"=>"success",
            "success"=>true,
            "products"=>$products
        ]));

    }else{
        //close and end connection and return a failed message when no data was return from the db
        exit(json_encode([
            "status"=>"failed",
            "success"=>false,
            "error" => "No products found"
        ]));
    }
    
    //close and end connection
    exit;
