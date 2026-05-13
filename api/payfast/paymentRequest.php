<?php
    require '../../config/cors.php';//allow access from webserver
    require '../../config/protectedRoute.php';//user must be authorised
    $conn = require '../../config/dbconn.php';//connect to DB

    require './generateSignature.php';//user must be authorised

    //get info from client
    $jsonData = json_decode(file_get_contents('php://input'),true);

    if(!isset($jsonData["productID"]) || empty(trim($jsonData["productID"]))){
        echo json_encode([
            "status" => "failed",
            "success" => false,
            "error" => "Fill in all fields",
            "fields" => $jsonData
        ]);
        exit;
    }

    $productID = $jsonData["productID"];

    $userID = $_SESSION["userID"];

    //return error when no userID was found
    if(!isset($userID) || empty(trim($userID))){
        exit(json_encode([
            "status"=>"failed",
            "success"=>false,
            "error" => "Could not process payment, No user-ID found."
        ]));
    }


    //check that the user is not the owner of the product
    try {
        $productStmt = $conn->prepare("SELECT ownerID, name, price FROM products WHERE productID = ? AND sold = 0 LIMIT 1");
        $productStmt->bind_param("s", $productID);

        $productStmt->execute();

        $product_result = $productStmt->get_result();

        $productInfo = [];
        
        if($product_result && $product_result->num_rows > 0){
            $productInfo = $product_result->fetch_assoc();

            //check if user is the owner
            if($userID == $productInfo["ownerID"]){
                throw new Exception("You cannot buy from yourself.");
            }


        }else{
            throw new Exception("No listings found with provided ID.");
        }

        //append item to echo
        $ordersStmt = $conn->prepare("
            INSERT INTO orders 
            (sellerID, buyerID, productID, price, platform_Fee, seller_Fee, status) 
            values 
            (?, ?, ?, ?, ?, ?, ?)
        ");

        
        $sellerID = $productInfo["ownerID"];
        $buyerID = $userID;

        $price = $productInfo["price"];
        $platformFee = ($price / 100) * 5;//platform takes 5 percent
        $sellerFee = $price - $platformFee;

        $status = "order placed";

        $ordersStmt->bind_param("sssddds", $sellerID, $buyerID, $productID, $price, $platformFee, $sellerFee, $status);
        $ordersStmt->execute();


        //check if order was placed successfully
        $paymentID = $conn->insert_id;
        if($paymentID <= 0){
            throw new Exception("Could not place order.");
        }

        //get buyer INfo
        $buyerStmt = $conn->prepare("SELECT fName, lName, email FROM users WHERE userID = ?");
        $buyerStmt->bind_param("s", $userID);
        $buyerStmt->execute();

        $buyer_results = $buyerStmt->get_result();

        $buyerInfo = [];
        if($buyer_results && $buyer_results->num_rows > 0){
            $buyerInfo = $buyer_results->fetch_assoc();
        }else{
            throw new Exception("Buyer information could not be found.");
        }

        // Construct variables
        $cartTotal = $price; // This amount needs to be sourced from your application
        $passphrase = 'qwefasfawqeqwqweasd';

        $data = array(
            // Merchant details
            'merchant_id' => '10046638',
            'merchant_key' => 'ntk5urzc2lda3',
            'return_url' => 'https://easymarket-727523185751.europe-west1.run.app/',
            'cancel_url' => 'https://easymarket-727523185751.europe-west1.run.app/',
            'notify_url' => 'https://easymarket-727523185751.europe-west1.run.app/api/payfast/paymentNotification.php',
            // Buyer details
            'name_first' => $buyerInfo["fName"],
            'name_last'  => $buyerInfo["lName"],
            'email_address'=> $buyerInfo["email"],
            // Transaction details
            'm_payment_id' => $paymentID, //Unique payment ID to pass through to notify_url
            'amount' => number_format( sprintf( '%.2f', $cartTotal ), 2, '.', '' ),
            'item_name' => $productInfo["name"]
        );

        $signature = generateSignature($data, $passphrase);
        $data['signature'] = $signature;

        // If in testing mode make use of either sandbox.payfast.co.za or www.payfast.co.za
        $testingMode = true;
        $pfHost = $testingMode ? 'sandbox.payfast.co.za' : 'www.payfast.co.za';
        $htmlForm = "";//'<form action="https://'.$pfHost.'/eng/process" method="post">';
        foreach($data as $name=> $value)
        {
            $htmlForm .= '<input name="'.$name.'" type="hidden" value=\''.$value.'\' />';
        }

        echo json_encode([
            "status"=>"success",
            "success"=>true,
            "product" => $productInfo,
            "buyerInfo" =>$buyerInfo,
            "form" => $htmlForm
        ]);

    } catch (\Throwable $th) {
        exit(json_encode([
            "status"=>"failed",
            "success"=>false,
            "error" => $th->getMessage()
        ]));
    }

    







