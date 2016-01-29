<?php
require_once("../../../init.php");
require("lib/Tools.php");
Tools::createEmailTemplates();
$q = 'CREATE TABLE IF NOT EXISTS `tblasciotlds` (`Tld` char(255) NOT NULL, `Threshold` int(11) NOT NULL, `Renew` tinyint(1) NOT NULL, `LocalPresenceRequired` tinyint(1) NOT NULL, `LocalPresenceOffered` tinyint(1) NOT NULL, `AuthCodeRequired` tinyint(1) NOT NULL, `Country` char(255) NOT NULL, UNIQUE KEY `tld` (`Tld`) )';
mysql_query($q);
if(mysql_error()) echo mysql_error();
$q = 'CREATE TABLE IF NOT EXISTS `tblasciojobs` (`id` int(11) NOT NULL AUTO_INCREMENT, `last_id` int(11) NOT NULL, `order_id` char(255) NOT NULL, `method` char(255) NOT NULL, `request` text NOT NULL, `response` text NOT NULL, `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (`id`), KEY `last_id` (`last_id`), KEY `order_id` (`order_id`) )';
mysql_query($q);
if(mysql_error()) echo mysql_error();
'CREATE TABLE IF NOT EXISTS `tblasciohandles` (`type` varchar(256) NOT NULL, `whmcs_id` int(10) NOT NULL, `ascio_id` varchar(256) NOT NULL, PRIMARY KEY (`whmcs_id`), KEY `ascio_id` (`ascio_id`) )';
mysql_query($q);
if(mysql_error()) echo mysql_error();



$tldsString = file_get_contents("http://aws.ascio.info/tldkit.xq");
$tlds = json_decode($tldsString);

foreach ($tlds->tld as $key => $tld) {	
	echo "insert: ".$tld->tld."\n";
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