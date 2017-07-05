<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

function curl_get($url, array $get = NULL, array $options = array()) 
{    
    $defaults = array( 
        CURLOPT_URL => $url,
        CURLOPT_HEADER => 0, 
        CURLOPT_RETURNTRANSFER => TRUE, 
        CURLOPT_TIMEOUT => 4 
    ); 
    
    $ch = curl_init(); 
    curl_setopt_array($ch, ($options + $defaults)); 
    if( ! $result = curl_exec($ch)) 
    { 
        trigger_error(curl_error($ch)); 
    } 
    curl_close($ch); 
    return $result; 
} 
$result = curl_get("https://aws.ascio.com/2012/01/01/AscioService.wsdl",NULL,array());
if(strpos($result, "wsdl")) echo "<h1>Successfully connected to live environment</h1>";
else echo "<h1>Error connecting Ascio live</h1><p>Please try: <code>traceroute aws.ascio.com<code> on the linux command-line  </p>";

$result = curl_get("https://aws.demo.ascio.com/2012/01/01/AscioService.wsdl",NULL,array());
if(strpos($result, "wsdl")) echo "<h1>Successfully connected to test environment</h1>";
else echo "<h1>Error connecting Ascio ote</h1><p>Please try: <code>traceroute aws.demo.ascio.com<code> on the linux command-line  </p>";

?>

