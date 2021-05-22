<?php

include 'utilities/utilities.php';

$mysqli = util::getMysqli();

$currentDate = date("Y-m-d");
$previousDate = date("Y-m-d", strtotime("-1 day"));

$ch = curl_init();

$payload = '{"queryString":"SELECT ID,AccountId,Amount,Status,EffectiveDate FROM Payment WHERE EffectiveDate>'.$previousDate.'"}';

$headers = array(
'Content-Type: application/json',
'apiAccessKeyId: api@focusnfly.com',
'apiSecretAccessKey: welcome'    
);

curl_setopt($ch, CURLOPT_URL, "https://rest.zuora.com/v1/action/query" );
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
curl_setopt($ch, CURLOPT_POST,           1 );
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload); 
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 

$result=curl_exec ($ch);
$array = json_decode($result, true); 
foreach($array['records'] as $info){
    
    $insert = "INSERT INTO allTransactions(ID, AccountId, Amount, Statuss, EffectiveDate) VALUES('".$info["Id"]."', '".$info["AccountId"]."', '".$info["Amount"]."', '".$info["Status"]."', '".$info["EffectiveDate"]."')";
    
    $mysqli->query($insert);        
    
}

curl_close($ch);
?>
