<?php
function addContactFields($params,$contact,$type) {
	$tld = $params["tld"];
	if(!file_exists(realpath(dirname(__FILE__))."/".$tld.".php")) return $contact;
	require_once($tld.".php");
	$fn = $tld . "ContactFields";
	if(function_exists($fn)) {
		return $fn($params,$contact,$type);	
	} else {
		return $contact;
	}
}
function addDomainFields($params,$domain) {
	$tld = $params["tld"];
	if(!file_exists(realpath(dirname(__FILE__))."/".$tld.".php")) return $domain;
	require_once($tld.".php");
	$fn = $tld . "DomainFields";
	if(function_exists($fn)) {
		return $fn($params,$domain);	
	} else {
		return $domain;
	}
}
?>