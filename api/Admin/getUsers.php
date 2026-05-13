<?php
    header('Content-Type: application/json');

    //catch and return errors
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    require '../../config/cors.php';//allow access from webserver
    require '../../config/protectedRoute.php';//user must be authorised
    require '../../config/adminOnly.php';//user must be authorised
    $conn = require '../../config/dbconn.php';//connect to DB

    

    $jsonData = json_decode(file_get_contents('php://input'),true);

    // Get page number from client
    $page = isset($jsonData['page']) ? (int)$jsonData['page'] : 1;
    $limit = 10;

    // Calculate offset
    $offset = ($page - 1) * $limit;

    //check if data is set and not empty
    if (!isset($jsonData["page"])) {
        echo json_encode([
                "status" => "failed",
                "success" => false,
                "error" => "Fill in all fields",
                "fields" => $jsonData
            ]);
            exit;
    }



    //create thee cache dir if not exist
    $cacheDir = __DIR__ . "/users/cache";
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0777, true);
    }


    $cacheKey = md5("$page");
    $cacheFile = $cacheDir . "/$cacheKey.json";

    // check cache
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 120) {
        echo json_encode([
            "status" => "success",
            "success" => true,
            "users" => json_decode(file_get_contents($cacheFile), true)
        ]);
        exit;
    }


    $sql = "SELECT userID, email, fName, lName, city, province, (upvotes - downvotes) AS Rating FROM users LIMIT ? OFFSET ?";

    $getUsersStmt = $conn->prepare($sql);

    $getUsersStmt->bind_param("ii", $limit, $offset);

    $getUsersStmt->execute();

    //get data from db
    $users_result = $getUsersStmt->get_result();
    

    $users = [];

    //check if we have received rows form the db
    if ($users_result && $users_result->num_rows > 0) {
        while ($user = $users_result->fetch_assoc()) {
            $users[] = $user;
        }

        echo json_encode([
            "status" => "success",
            "success" => true,
            "users" => $users,
            "fields" => $jsonData
        ]);


        //add data to cache
        file_put_contents($cacheFile, json_encode($users));
    }else{
        echo json_encode([
            "status" => "failed",
            "success" => false,
            "error" => "No users found",
            "fields" => $jsonData
        ]);
    }

    


    
    //close and end connection
    $getUsersStmt->close();
    $conn->close();
    die();
