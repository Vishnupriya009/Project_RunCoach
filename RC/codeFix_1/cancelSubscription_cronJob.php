<?php

include 'utilities/utilities.php';
include 'phpmailler/sendmail.php';

$mysqli = util::getMysqli();

$accountData = array();
$subscriptionData = array();

$headers = array(
'Content-Type: application/json',
'apiAccessKeyId: api@focusnfly.com',
'apiSecretAccessKey: welcome'    
);

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
    
       $ch = curl_init();

       $payload = '{"queryString": "select WorkEmail from contact WHERE AccountId = '."'$accountKey'".'"}';

       curl_setopt($ch, CURLOPT_URL, "https://rest.zuora.com/v1/action/query" );
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
       curl_setopt($ch, CURLOPT_POST,           1 );
       curl_setopt($ch, CURLOPT_POSTFIELDS, $payload); 
       curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 

       $contactResult=curl_exec ($ch);
       $contactArray = json_decode($contactResult, true); 
    
       $firstName = $contactArray['records'][0]['FirstName']; 
       $eMail = $contactArray['records'][0]['WorkEmail'];
    
       curl_close($ch);
       
       while ($fetchUser = mysqli_fetch_array($selectUser)) {
             $userStatus = $fetchUser['Statuss'];
             if($userStatus == 'Error')
             {
                $range++;
             }
       }
             if($range == 5 || $range == 10 || $range == 15){
                 switch ($range) {
                                      case 5:
                                        $emailBody = $firstName.",<br><br>
                                        We are unable to process payment for the subscription associated with the <a href='http://runcoach.com/'> Runcoach </a>. Based on the payment information there are 5 failed payment transaction attemps. Please update your payment details to avoid subscription cancellation.";
                                        break;
                                      case 10:
                                        $emailBody = $firstName.",<br><br>
                                        We are unable to process payment for the subscription associated with the <a href='http://runcoach.com/'> Runcoach </a>. Based on the payment information there are 10 failed payment transaction attemps. Please update your payment details to avoid subscription cancellation.";
                                        break;
                                      case 15:
                                        $emailBody = $firstName.",<br><br>
                                        We are unable to process payment for the subscription associated with the <a href='http://runcoach.com/'> Runcoach </a>. Based on the payment information there are 15 failed payment transaction attemps. Please update your payment details to avoid subscription cancellation.";
                                        break;
                                    }
                 $mail_to = $eMail . ', ' . 'info@runcoach.com';
	             $subject = "Please act now to keep your subscription";
	             $body = $emailBody;
                 $headers  = 'MIME-Version: 1.0' . "\r\n";
                 $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
                 $headers .= 'From: runcoach <info@runcoach.com>' . "\r\n";
                 send_sg_smtp_mail('info@runcoach.com','runcoach',$subject,$mail_to,$body,$body);
                 
             }
    
             if($range == 20){
               if(!in_array($accountKey, $accountData)){
                 $accountData[] = array($accountKey);
                
                 $mail_to = $eMail . ', ' . 'info@runcoach.com';
	             $subject = "Subscription Cancelled";
	             $body = $firstName.",<br><br>
                         After several attempts we are unable to process payment for the subscription associated with the <a href='http://runcoach.com/'> Runcoach </a>. Therefore your subscription has been cancelled. In order to activate a new subscription visit <a href='http://runcoach.com/'> Runcoach </a>.";
                 $headers  = 'MIME-Version: 1.0' . "\r\n";
                 $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
                 $headers .= 'From: runcoach <info@runcoach.com>' . "\r\n";
                 send_sg_smtp_mail('info@runcoach.com','runcoach',$subject,$mail_to,$body,$body);   
               }     
            }   
}

$ch = curl_init();

foreach($accountData as $accountIds){
    $id = $accountIds[0];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://rest.zuora.com/v1/subscriptions/accounts/'.$id.'' );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 

    $subscriptionResult = curl_exec($ch);
    $subscriptionArray = json_decode($subscriptionResult, true);
    
    foreach($subscriptionArray['subscriptions'] as $subscriptions){
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
                    "cancellationEffectiveDate" => date("Y/m/d")
                  );
                                   
    $jsonDataEncoded = json_encode($data);
    
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonDataEncoded);
    
    $cancelSubscriptionResult = curl_exec($ch);
    $cancelArray = json_decode($cancelSubscriptionResult, true); 
    
    foreach($cancelArray as $subscriptionCancel){
    
    $invoiceId = $subscriptionCancel["invoiceId"]; 
    $ch2 = curl_init();
    curl_setopt($ch2, CURLOPT_URL, 'https://rest.zuora.com/v1/object/invoice/'.$invoiceId.'' );
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1 );    
    curl_setopt($ch2, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "DELETE");
    $invoiceResult = curl_exec($ch2); 
    $invoiceArray = json_decode($invoiceResult, true);
    $invoiceStatus =  $invoiceArray['success'];   
    $insert = "INSERT INTO cancelledSubscriptions(SubscriptionId, AccountId, InvoiceId, SubscriptionCancelStatus, InvoiceCancelStatus, CancelledDate) VALUES('".$subscriptionCancel["subscriptionId"]."', '".$IdAccount."', '".$subscriptionCancel["invoiceId"]."', '".$subscriptionCancel["success"]."', '".$invoiceStatus."', '".$subscriptionCancel["cancelledDate"]."')";
    
    $mysqli->query($insert);  
        
    curl_close($ch2);
}
}
curl_close($ch);

?>