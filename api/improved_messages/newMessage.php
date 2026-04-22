<?php
    header('Content-Type: application/json');//return data in json format

    //catch and return errors
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    // import env variables
    try {
        require_once __DIR__ . '/../../vendor/autoload.php';
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
        $dotenv->load();
    } catch (\Throwable $th) {
        echo json_encode([
                "status"=>"failed",
                "success"=>false,
                "error"=>"Could not load environment variables"
            ]);
        die();
    }

    require '../../config/cors.php';//allow access from webserver
    require '../../config/protectedRoute.php';//user must be authorised
    require '../../config/dbconn.php';//connect to DB

    require_once "./message_encryption.php";

    $result = json_decode(file_get_contents("php://input"), true);

    if (
        !isset($result['productID'], $result['message']) 
        ||
        empty($result['productID']) || empty(trim($result['message']))
    ) {
        echo json_encode([
            "status"=>"failed",
            "success"=>false,
            "error"=>"Fill in all fields"
        ]);
        exit;
    }

    // Get data
    $productID = $result['productID'];
    $message = trim($result['message']);

    //get userID from session
    $userID = $_SESSION['userID'];

    

    try {
        //start a transaction so we can revert all inserts if one fails
        $conn->begin_transaction();

        //check if we received a groupID
        if (!isset($result['groupID']) || empty($result['groupID']) || $result['groupID'] == 0)
        {
            //no groupID received

            //get ownerID
            $getOwnerIDStmt = $conn->prepare("SELECT ownerID from products WHERE productID = ?");
            $getOwnerIDStmt->bind_param("s", $productID);
            $getOwnerIDStmt->execute();

            $owner_result = $getOwnerIDStmt->get_result();
            
            //check for valid ownerID
            if($owner_result->num_rows <= 0){
                throw new Exception("Could not find the owner of the product", 1);
            }

            //get product's ownerID
            $row = $owner_result->fetch_assoc();
            $ownerID = $row["ownerID"];

            if($ownerID == $userID){
                throw new Exception("You cannot message yourself", 1);
            }

            //check if the user already belongs to the group
            $checkIfINGroup = $conn->prepare("
                SELECT groupID 
                FROM group_chats 
                WHERE productID = ? 
                AND 
                (buyerID = ? AND ownerID = ?) 
                LIMIT 1
            ");

            $checkIfINGroup->bind_param("sss", $productID, $userID, $ownerID);
            $checkIfINGroup->execute();

            $checkIfINGroup_result = $checkIfINGroup->get_result();
            

            if($checkIfINGroup_result->num_rows <= 0){
                //if user not currently in a group_chat with product owner for this product

                //create the group_chat between the 2 users for the current product
                $createGroupChatStmt = $conn->prepare("INSERT INTO group_chats 
                    (productID, buyerID, ownerID) 
                    VALUES 
                    (?,?,?)"
                );

                $createGroupChatStmt->bind_param("sss", $productID, $userID, $ownerID);
                $createGroupChatStmt->execute();

                //get groupID
                $groupID = $conn->insert_id;

            }else{
                //user already in group_chat with product owner
                $row = $checkIfINGroup_result->fetch_assoc();
                $groupID = $row["groupID"];
            }



            
        }else{
            //if a groupID was sent from client
            $groupID = $result['groupID'];

            //check if group Exist
            $getGroupChatIdStmt = $conn->prepare("SELECT groupID, buyerID FROM group_chats WHERE groupID = ?");
            $getGroupChatIdStmt->bind_param("i", $groupID);
            $getGroupChatIdStmt->execute();
            
            $group_result = $getGroupChatIdStmt->get_result();
            $row = $group_result->fetch_assoc();
            $groupID = $row["groupID"];

            //check if group exist
            if(empty($groupID)){
                throw new Exception("The current user does not belong to any groups with the following ID: " . $groupID , 1);  
            }
        }

        //fallback parameter to cancel transaction if no group was created
        if (!$groupID) {
            throw new Exception("Could not get groupID", 501);
        }


        //INSERT message
        $addMessageStmt = $conn->prepare("INSERT INTO chats 
            (groupID, sentByID, message, message_read) 
            VALUES 
            (?,?,?,false)"
        );

        //encrypt message
        $encryptedMessage = encryptMessage($message);

        $addMessageStmt->bind_param("sss", $groupID, $userID, $encryptedMessage);
        $addMessageStmt->execute();

        //check if message was successfully added
        $messageID = $conn->insert_id;

        if(!$messageID){
            throw new Exception("Could not send message", 501);  
        }


        //successfully added the message
        $conn->commit();
        //return success message after comment have been added
        echo json_encode([
            "status"=>"success",
            "success"=>true,
            "message"=>"Message sent"
        ]);

    } catch (\Throwable $th) {
        //Rollback if anything fails
        $conn->rollback();

        //return products in json format
        echo json_encode([
            "status"=>"failed",
            "success"=>false,
            "error"=>$th->getMessage()
        ]);
    }

    //Close statements and connection
    if (isset($getOwnerIDStmt)) $getOwnerIDStmt->close();
    if (isset($getGroupChatIdStmt)) $getGroupChatIdStmt->close();
    if (isset($createGroupChatStmt)) $createGroupChatStmt->close();
    if (isset($addMessageStmt)) $addMessageStmt->close();

    $conn->close();
    die();