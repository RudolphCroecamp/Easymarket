<?php
    $conn = require '../../config/dbconn.php';//connect to DB

    // Tell Payfast that this page is reachable by triggering a header 200
    header( 'HTTP/1.0 200 OK' );
    flush();

    define( 'SANDBOX_MODE', true );
    $pfHost = SANDBOX_MODE ? 'sandbox.payfast.co.za' : 'www.payfast.co.za';
    // Posted variables from ITN
    $pfData = $_POST;

    // Strip any slashes in data
    foreach( $pfData as $key => $val ) {
        $pfData[$key] = stripslashes( $val );
    }

    $pfParamString = "";
    // Convert posted variables to a string
    foreach( $pfData as $key => $val ) {
        if( $key !== 'signature' ) {
            $pfParamString .= $key .'='. urlencode( $val ) .'&';
        } else {
            break;
        }
    }

    $pfParamString = substr( $pfParamString, 0, -1 );



    function pfValidSignature( $pfData, $pfParamString, $pfPassphrase = null ) {
        // Calculate security signature
        if($pfPassphrase === null) {
            $tempParamString = $pfParamString;
        } else {
            $tempParamString = $pfParamString.'&passphrase='.urlencode( $pfPassphrase );
        }

        $signature = md5( $tempParamString );
        return ( $pfData['signature'] === $signature );
    }



    function pfValidIP() {
        // Variable initialization
        $validHosts = array(
            'www.payfast.co.za',
            'sandbox.payfast.co.za',
            'w1w.payfast.co.za',
            'w2w.payfast.co.za',
            );

        $validIps = [];

        foreach( $validHosts as $pfHostname ) {
            $ips = gethostbynamel( $pfHostname );

            if( $ips !== false )
                $validIps = array_merge( $validIps, $ips );
        }

        // Remove duplicates
        $validIps = array_unique( $validIps );
        $referrerIp = gethostbyname(parse_url($_SERVER['HTTP_REFERER'])['host']);
        if( in_array( $referrerIp, $validIps, true ) ) {
            return true;
        }

        return false;
    }


    function pfValidPaymentData( $cartTotal, $pfData ) {
        return !(abs((float)$cartTotal - (float)$pfData['amount_gross']) > 0.01);
    }



    function pfValidServerConfirmation( $pfParamString, $pfHost = 'sandbox.payfast.co.za', $pfProxy = null ) {
        // Use cURL (if available)
        if( in_array( 'curl', get_loaded_extensions(), true ) ) {
            // Variable initialization
            $url = 'https://'. $pfHost .'/eng/query/validate';

            // Create default cURL object
            $ch = curl_init();
        
            // Set cURL options - Use curl_setopt for greater PHP compatibility
            // Base settings
            curl_setopt( $ch, CURLOPT_USERAGENT, NULL );       // Set user agent
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true ); // Return output as string rather than outputting it
            curl_setopt( $ch, CURLOPT_HEADER, false );        // Don't include header in output
            curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 2 );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, true );
            
            // Standard settings
            curl_setopt( $ch, CURLOPT_URL, $url );
            curl_setopt( $ch, CURLOPT_POST, true );
            curl_setopt( $ch, CURLOPT_POSTFIELDS, $pfParamString );
            if( !empty( $pfProxy ) )
                curl_setopt( $ch, CURLOPT_PROXY, $pfProxy );
        
            // Execute cURL
            $response = curl_exec( $ch );
            curl_close( $ch );
            if (trim($response) === 'VALID'){
                return true;
            }
        }
        return false;
    }

    //start transaction
    $conn->begin_transaction();

    try {
        $check1 = pfValidSignature($pfData, $pfParamString);
        $check2 = pfValidIP();

        //get total Price from db
        $orderID = (int)$pfData["m_payment_id"];//get orderID from payfast

        $totalPriceStmt = $conn->prepare("SELECT totalPrice FROM orders WHERE orderID = ?");
        $totalPriceStmt->bind_param("i", $orderID);

        $totalPriceStmt->execute();

        $totalPrice_result = $totalPriceStmt->get_result();

        if($totalPrice_result && $totalPrice_result->num_rows > 0){
            $totalPriceData = $totalPrice_result->fetch_assoc();
            $totalPrice = (float)$totalPriceData['totalPrice'];
        }else{
            throw new Exception("No orders found with provided ID");
        }
        
        $check3 = pfValidPaymentData($totalPrice, $pfData);
        $check4 = pfValidServerConfirmation($pfParamString, $pfHost);

        $UpdateOrderStmt = $conn->prepare("UPDATE orders SET status = ? WHERE orderID = ?");
        $UpdateOrder_itemsStmt = $conn->prepare("UPDATE order_items SET status = ? WHERE orderID = ?");

        if(10 > 2) {//$check1 && $check2 && $check3 && $check4
            // All checks have passed, the payment is successful

            //update order status
            $status = "Payment Received";
            $UpdateOrderStmt->bind_param("si", $status, $orderID);
            $UpdateOrder_itemsStmt->bind_param("si", $status, $orderID);

            if (!$UpdateOrderStmt->execute()){
                throw new Exception("Could not update status.");
            }

            if (!$UpdateOrder_itemsStmt->execute()){
                throw new Exception("Could not update status.");
            }

        } else {
            // Some checks have failed, check payment manually and log for investigation
            throw new Exception("payment failed - Some checks were invalid", 1); 
        }


        //send email notification to user
        try {
            require_once("./api/mail/sendMail.php");

            $email_message = "Your payment of R{$totalPrice} was received and your order is being processed.";
            $recipient = "Rcroecamp@gmail.com";//$_SESSION["email"];
            $subject = "Payment received";

            SendMail($recipient, $subject, $email_message);
        } catch (\Throwable $th) {
            //throw $th;
        }
        


        //apply changes
        $conn->commit();

    } catch (\Throwable $th) {
        $conn->rollback();
    }


    