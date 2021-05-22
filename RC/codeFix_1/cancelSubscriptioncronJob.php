<?php

include 'utilities/utilities.php';

$mysqli = util::getMysqli();

$accountData = array();
$subscriptionData = array();

$currentDate = date("Y-m-d");
$previousDate = date("Y-m-d", strtotime("-1 day"));

$select = $mysqli->query("SELECT * FROM allTransactions 
                          WHERE EffectiveDate BETWEEN '" . $previousDate . "' AND  '" . $currentDate . "'");

while( $fetchtransactions =  mysqli_fetch_array($select)){  
       $accountKey = $fetchtransactions['AccountId'];
       $selectUser = $mysqli->query("SELECT * 
                                     FROM allTransactions 
                                     WHERE AccountId =  '$accountKey' 
                                     ORDER BY EffectiveDate DESC LIMIT 20");
       $range = 0;    
       while ($fetchUser = mysqli_fetch_array($selectUser)) {
             $userStatus = $fetchUser['Statuss'];
             if($userStatus == 'Error')
             {
                $range++;
             }
       }
             if($range == 20){
               $accountData[] = array($accountKey);
            }   
}

$headers = array(
'Content-Type: application/json',
'apiAccessKeyId: api@focusnfly.com',
'apiSecretAccessKey: welcome'    
);

$ch = curl_init();

foreach($accountData as $accountIds){
    $id = $accountIds[0];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://rest.zuora.com/v1/subscriptions/accounts/'.$id.'' );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 

    $result = curl_exec($ch);
    $array = json_decode($result, true);
    
    foreach($array['subscriptions'] as $subscriptions){
    if($subscriptions['status'] == 'Active'){
        $subscriptionId = $subscriptions['id'];
        $subscriptionData[] = array($subscriptionId,$id);
    }
}
}
curl_close($ch);

foreach($subscriptionData as $subscriptionIds){
    $Idsubscription = $subscriptionIds[0];
    $IdAccount = $subscriptionIds[1];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://rest.zuora.com/v1/subscriptions/'.$Idsubscription.'/cancel' );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    
    $data = array(
                    "cancellationPolicy"        => 'SpecificDate',
                    "cancellationEffectiveDate" => date("Y/m/d"),
                    "invoiceCollect"            => true
                  );
                                   
    $jsonDataEncoded = json_encode($data);
    
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonDataEncoded);
    
    $cancelSubscriptionResult = curl_exec($ch);
    $array2 = json_decode($cancelSubscriptionResult, true); 
    
    foreach($array2 as $subscriptionCancel){
    
    $insert = "INSERT INTO cancelledSubscriptions(SubscriptionId, AccountId, InvoiceId, Status, CancelledDate) VALUES('".$subscriptionCancel["subscriptionId"]."', '".$IdAccount."', '".$subscriptionCancel["invoiceId"]."', '".$subscriptionCancel["success"]."', '".$subscriptionCancel["cancelledDate"]."')";
    
    $mysqli->query($insert);        
    
}
}
curl_close($ch);

?>
