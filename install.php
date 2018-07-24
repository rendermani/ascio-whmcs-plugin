<?php
require_once("../../../init.php");
require_once("lib/Tools.php");
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
check("init.php",file_exists("../../../init.php"));
check("registrarfunctions.php",file_exists("../../../includes/registrarfunctions.php"));

echo $lineBreak."* Creating email templates *";
Tools::createEmailTemplates();
echo $lineBreak."* Creating SQL tables".$lineBreak;
echo "- Creating tblasciotlds table".$lineBreak;
$q = 'CREATE TABLE IF NOT EXISTS `tblasciotlds` (`Tld` char(255) NOT NULL, `Threshold` int(11) NOT NULL, `Renew` tinyint(1) NOT NULL, `LocalPresenceRequired` tinyint(1) NOT NULL, `LocalPresenceOffered` tinyint(1) NOT NULL, `AuthCodeRequired` tinyint(1) NOT NULL, `Country` char(255) NOT NULL, UNIQUE KEY `tld` (`Tld`) )';
mysql_query($q);
if(mysql_error()) echo mysql_error().$lineBreak;
echo "- Creating tblasciojobs table".$lineBreak;
$q = 'CREATE TABLE IF NOT EXISTS `tblasciojobs` (`id` int(11) NOT NULL AUTO_INCREMENT, `last_id` int(11) NOT NULL, `order_id` char(255) NOT NULL, `method` char(255) NOT NULL, `request` text NOT NULL, `response` text NOT NULL, `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (`id`), KEY `last_id` (`last_id`), KEY `order_id` (`order_id`) )';
mysql_query($q);
if(mysql_error()) echo mysql_error().$lineBreak;
echo "- Creating tblasciohandles table".$lineBreak;
$q = 'CREATE TABLE IF NOT EXISTS `tblasciohandles` (`type` varchar(256) NOT NULL, `whmcs_id` int(10) NOT NULL, `ascio_id` varchar(256) NOT NULL, PRIMARY KEY (`whmcs_id`), KEY `ascio_id` (`ascio_id`) )';
mysql_query($q);
if(mysql_error()) echo mysql_error().$lineBreak;
echo "- Creating mod_asciosession table".$lineBreak;
$q = 'CREATE TABLE IF NOT EXISTS `mod_asciosession` (`account` varchar(255) NOT NULL, `sessionId` varchar(255) NOT NULL, `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, UNIQUE KEY `account` (`account`), KEY `date` (`timestamp`) )';
mysql_query($q);
if(mysql_error()) echo mysql_error()."\n<br>";

echo "- Adding product to tlbasciohandles table (update)".$lineBreak;
$q = 'ALTER TABLE `tblasciohandles` ADD `domain` VARCHAR(255) NOT NULL AFTER `ascio_id`, ADD INDEX `domain` (`domain`);';
mysql_query($q);
if(mysql_error()) echo mysql_error()."\n<br>";


echo $lineBreak."* Read TLD parameters *".$lineBreak;
$s = curl_init(); 
curl_setopt($s,CURLOPT_URL,"http://aws.ascio.info/tldkit.xq"); 
curl_setopt($s,CURLOPT_RETURNTRANSFER,true);
$tldsString = curl_exec($s);
$tlds = json_decode($tldsString);

foreach ($tlds->tld as $key => $tld) {	
	echo "+ insert: ".$tld->tld.$lineBreak;
	flush();
	$result = select_query("tblasciotlds","*",array("Tld" => $tld->tld));
	if($result) mysql_query("delete from tblasciotlds where Tld='".$tld->tld."'");
	$data = array(
		"Tld" => $tld->tld,
		"Threshold" => $tld->Threshold,
		"Renew" => $tld->Renew == "true" ? 1 : 0,
		"LocalPresenceRequired" => $tld->LocalPresenceRequired == "true" ? 1 : 0,
		"LocalPresenceOffered" => $tld->LocalPresenceOffered == "true" ? 1 : 0,
		"AuthCodeRequired" => $tld->AuthCodeRequired == "true" ? 1 : 0,
		"Country" => $tld->Country
	);
	insert_query("tblasciotlds",$data);
	if(mysql_error()) die("error: ".mysql_error());
}


?>