<?php
require_once("../../../init.php");
require("lib/Tools.php");
Tools::createEmailTemplates();

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