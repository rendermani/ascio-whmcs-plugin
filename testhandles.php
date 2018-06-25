<?php 
error_reporting(E_ALL);
ini_set('error_reporting', E_ERROR);
ini_set('display_errors', "on");

require_once(realpath(dirname(__FILE__))."/../../../init.php");
require_once realpath(dirname(__FILE__))."/../../../includes/registrarfunctions.php";
require_once(realpath(dirname(__FILE__))."/lib/Request.php");

$cfg = getRegistrarConfigOptions("ascio");
$request = new Request($cfg);
$result = $request->availabilityInfo("test.de");
var_dump($result);