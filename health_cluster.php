<?php

/**
 * Health check for the cluster, please enter your cluster urls. 
 * I a domain of the last poll-message was not found on any servers, the result will be 404 - message. 
 * This script can be added to a monitoring tool. It shouldn't be run too often. Daily or hourly should be good. 
 * Also a threshold can be used in the monitoring tool, like Alert when 5 attempts. 
 * It can be placed anywhere
 */

function getStatus ($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $content = curl_exec($ch);
    $code = curl_getinfo($ch,CURLINFO_HTTP_CODE);
    curl_close($ch);
    return (object) ["code"  => $code,"content" => $content];
}

$urls = [
    "http://localhost/whmcs/modules/registrars/ascio/health.php",
    "http://localhost/whmcs/modules/registrars/ascio/health.php"
];

foreach($urls as $key => $url) {    
   $status =  getStatus($url);
   if($status->code==200) {
       echo "OK";
       return;
   }
   $error = str_replace("\n","",$status->content."s"); 
}
header("HTTP/1.0 404 ".$error);
echo $error;
