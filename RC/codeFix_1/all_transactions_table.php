<?php

$serverName = "development-database.cpqaqw6bggfw.us-west-2.rds.amazonaws.com";
$userName = "renga";
$password = "rcPass1234!";
$dbName = "renga_fnfprod";

$conn = mysqli_connect($serverName, $userName, $password, $dbName);

if($conn->connect_error){
    die("connection failed:" .$conn->connection_error);
}

$createTable = "CREATE TABLE allTransactions(
                                              ID VARCHAR(2000) NOT NULL,
                                              AccountId VARCHAR(2000) NOT NULL,
                                              Amount DECIMAL(6,2),
                                              Statuss VARCHAR(500) NOT NULL,
                                              EffectiveDate DATE NOT NULL
                                            )";

if(mysqli_query($conn, $createTable)){
    echo "Table created successfully.";
} else{
    echo "ERROR: Could not able to execute $createTable. " . mysqli_error($conn);
}
for ($i = 2000; $i<=86000;){
$ch = curl_init();

$payload = '{"queryLocator": "2c92a00c74356b82017448e020415563-'.$i.'"}';

$headers = array(
'Content-Type: application/json',
'apiAccessKeyId: api@focusnfly.com',
'apiSecretAccessKey: welcome'    
);

curl_setopt($ch, CURLOPT_URL, "https://rest.zuora.com/v1/action/queryMore" );
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
curl_setopt($ch, CURLOPT_POST,           1 );
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload); 
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 

$result=curl_exec ($ch);
$array = json_decode($result, true); 
foreach($array['records'] as $info){
    
    $insert = "INSERT INTO allTransactions(ID, AccountId, Amount, Statuss, EffectiveDate) VALUES('".$info["Id"]."', '".$info["AccountId"]."', '".$info["Amount"]."', '".$info["Status"]."', '".$info["EffectiveDate"]."')";
    
    mysqli_query($conn, $insert);        
    
}

curl_close($ch);
$i = $i+2000;    
}
$cancelSubscriptionTable = "CREATE TABLE cancelledSubscriptions(
                                              SubscriptionId VARCHAR(2000) NOT NULL,
                                              AccountId VARCHAR(2000) NOT NULL,
                                              InvoiceId VARCHAR(2000) NOT NULL,
                                              SubscriptionCancelStatus INT NOT NULL,
                                              InvoiceCancelStatus INT,
                                              CancelledDate DATE NOT NULL
                                            )";
 
if(mysqli_query($conn, $cancelSubscriptionTable)){
    echo "Table created successfully.";
} else{
    echo "ERROR: Could not able to execute $cancelSubscriptionTable. " . mysqli_error($conn);
}

mysqli_close($conn);

?>
