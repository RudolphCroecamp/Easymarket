<?php
  $url = "https://api.paystack.co/transaction/initialize";

  // $data = json_decode(file_get_contents("php://input"), true);

  $cus_email = $_POST["cus_email"];
  $amount = $_POST["amount"];

  // if(!$_isset($cus_email) || $cus_email==""){
  //   echo "invalid customer email";
  //   die(["error" => "invalid custoemr email"]);
  // }

  // if(!$_isset($$amount) || $$amount==0){
  //   echo "invalid amuont";
  //   die(["error" => "invalid amount"]);
  // }else{
  //   if($amount <=0 || $amount >= 100 * 1000){
  //     echo "amount out of range";
  //     die(["error" => "amount out of range"]);
  //   }
  // }

  $fields = [
    'email' => $cus_email,//receipt email
    'amount' => $amount * 100,
    'callback_url' => "https://hello.pstk.xyz/callback",
    'metadata' => ["cancel_action" => "https://your-cancel-url.com"]
  ];

  $fields_string = http_build_query($fields);

  //open connection
  $ch = curl_init();
  
  //set the url, number of POST vars, POST data
  curl_setopt($ch,CURLOPT_URL, $url);
  curl_setopt($ch,CURLOPT_POST, true);
  curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    "Authorization: Bearer sk_test_fabcdbd8fd3d25a3aedc6539aa986bfef4128a15",
    "Cache-Control: no-cache",
  ));
  
  //So that curl_exec returns the contents of the cURL; rather than echoing it
  curl_setopt($ch,CURLOPT_RETURNTRANSFER, true); 
  
  //execute post
  $result = curl_exec($ch);

  echo $result;
?>