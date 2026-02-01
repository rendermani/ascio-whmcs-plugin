<?php
require_once("../../../init.php");
error_reporting(E_ALL);
ini_set('error_reporting', E_ERROR);
ini_set('display_errors', "on");
$isCLI = ( php_sapi_name() == 'cli' );
$lineBreak = $isCLI ? "\n" : "<br>\n";

function check($name,$value) {
	global $lineBreak;	
	if($value==true) {
		$ret =  "ok";
	} else {
	 	$ret = "failed";
	}
	echo "- Check ".$name.": ".$ret.$lineBreak;
	if($ret=="failed") die("Please fix the errors and retry".$lineBreak);
}
echo $lineBreak."* Check requirements *".$lineBreak;
check("Soap",class_exists("SoapClient"));

echo $lineBreak."* Creating SQL tables".$lineBreak;
echo "- Creating tblasciotlds table".$lineBreak;
$q = 'CREATE TABLE IF NOT EXISTS `mod_asciossl` (`id` int(8) NOT NULL, `user_id` int(8) NOT NULL, `order_id` char(10) NOT NULL, `certificate_id` char(20) NOT NULL, `type` char(255) NOT NULL, `period` int(2) NOT NULL, `status` char(100) NOT NULL, `token` char(100) NOT NULL, PRIMARY KEY (`id`) ) ENGINE=InnoDB DEFAULT CHARSET=latin1;';
mysql_query($q);
if(mysql_error()) echo mysql_error().$lineBreak;
echo "- Creating mod_asciosession table".$lineBreak;
$q = 'CREATE TABLE IF NOT EXISTS `mod_asciosession` (`account` varchar(255) NOT NULL, `sessionId` varchar(255) NOT NULL, `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, UNIQUE KEY `account` (`account`), KEY `date` (`timestamp`) )';
mysql_query($q);
if(mysql_error()) echo mysql_error()."\n<br>";

?>