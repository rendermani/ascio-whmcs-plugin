<?php
$wsdl = "https://aws.demo.ascio.com/2012/01/01/AscioService.wsdl";
//$wsdl = "https://aws.ascio.com/2012/01/01/AscioService.wsdl";
$client = new SoapClient($wsdl,array( "trace" => 1 ));

//AvailabilityInfo
$session= array(
	"Account" => "whmcsdemo",
	"Password" => "7fc0be8c74!A"
);
//LogIn

$logIn= array(
	"session" => $session
);
try{	
    $result = $client->logIn($logIn);
    echo "ResultCode : ".$result->LogInResult->ResultCode."\r\n";
    echo "ResultMessage : ".$result->LogInResult->Message."\r\n";
    if($result->LogInResult->Values) {
        foreach($result->LogInResult->Values as $key => $value) {
            echo $value->string."\r\n";
        }
    }
} catch(Exception $e) {
	echo $e->getMessage(); 
}
$availabilityInfo= array(
	"sessionId" => $result->sessionId,
	"domainName" => "netnames.com",
	"quality" => "Live"
);
try{	
    $result = $client->availabilityInfo($availabilityInfo);
    echo "ResultCode : ".$result->AvailabilityInfoResult->ResultCode."\r\n";
    echo "ResultMessage : ".$result->AvailabilityInfoResult->Message."\r\n";
    var_dump($result);
} catch(Exception $e) {
	echo $e->getMessage(); 
}


                    
