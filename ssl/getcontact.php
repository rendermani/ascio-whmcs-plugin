<?php
require_once(realpath(dirname(__FILE__))."/../../../init.php");
require_once("lib/Contacts.php");
require_once("lib/Params.php");
use ascio\whmcs\ssl as ssl;
use ascio\whmcs\ssl\SslContacts;
use ascio\whmcs\ssl\Params;
header('Content-Type: application/json');
$params = new Params();
$params->userId = $_SESSION["uid"];
$contacts = new SslContacts($params);
echo json_encode($contacts->getFromApi($_GET["contactId"],false));



