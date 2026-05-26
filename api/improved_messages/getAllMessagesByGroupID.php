<?php
    header('Content-Type: application/json');

    require '../../config/cors.php';//allow access from webserver
    require '../../config/protectedRoute.php';//user must be authorised
    $conn = require '../../config/dbconn.php';//connect to DB

    require_once "./message_encryption.php";

    // Get page number from client
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 100;

    // Calculate offset
    $offset = ($page - 1) * $limit;


    try {

        $result = json_decode(file_get_contents("php://input"), true);

        //check for correct data from client
        if (!isset($result["productID"]) || empty($result["productID"]) ) {
            throw new Exception("No productID recieved", 1);
            
        }

        $productID = $result["productID"];

        //get userID from session
        $userID = $_SESSION['userID'];

        //check for groupID
        $getGroupChatIdStmt = $conn->prepare("SELECT groupID, buyerID, productID FROM group_chats WHERE productID = ? AND (buyerID = ? OR ownerID = ?)");
        $getGroupChatIdStmt->bind_param("sss", $productID, $userID, $userID);
        $getGroupChatIdStmt->execute();
        
        $group_result = $getGroupChatIdStmt->get_result();
        
        
        //check if group exist
        if($group_result->num_rows<=0){
            throw new Exception("The current user does not belong to any group for the current product", 661);  
        }

        $row = $group_result->fetch_assoc();
        $groupID = $row["groupID"];
        $buyerID = $row["buyerID"];


        //get messages by groupID
        $getMessageStmt = $conn->prepare("SELECT * FROM chats where groupID = ? ORDER BY created_at ASC LIMIT ? OFFSET ?");
        $getMessageStmt->bind_param("iii", $groupID, $limit, $offset);
        $getMessageStmt->execute();

        $message_result = $getMessageStmt->get_result();

        $messages = [];


        //check if we have received rows form the db
        if ($message_result->num_rows > 0) {
            while ($row = $message_result->fetch_assoc()) {
                $row['message'] = decryptMessage($row['message']);
                $row["sentByCurrentUser"] = $row["sentByID"] == $userID ? true : false;
                $messages[] = $row;
            }
        }else{
            throw new Exception("No messages found", 1);
        }
        //return messages to client
        echo json_encode([
            "status"=>"success",
            "success"=>true,
            "messages"=> $messages,
            "isOwner" => $buyerID == $userID ? false : true
        ]);

    } catch (\Throwable $th) {
        $errorCode = $th->getCode();

        if($errorCode == 661){
            //return products in json format
            echo json_encode([
                "status"=>"failed",
                "success"=>false,
                "error"=>$th->getMessage()
            ]);
        }else{
            //return products in json format
            echo json_encode([
                "status"=>"failed",
                "success"=>false,
                "error"=>$th->getMessage()
            ]);
        }
        


    }

    //Close statements and connection
    if (isset($getGroupChatIdStmt)) $getGroupChatIdStmt->close();
    if (isset($getMessageStmt)) $getMessageStmt->close();

    $conn->close();
    die();
