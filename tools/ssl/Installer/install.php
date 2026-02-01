<?php

use ascio\whmcs\ssl\Installer;
require_once(__DIR__."/../../lib/Error.php");
require_once(realpath(dirname(__FILE__))."/../../../../../init.php");
require_once(__DIR__."/../Installer/Installer.php");

use ascio\whmcs\ssl\AscioSystemException;

header('Content-Type: application/json');

if($_SESSION["adminid"] < 1) {
    echo json_encode(["error" => "Invalid Session"] );
    die();
}
$git = "rendermani/ascio-ssl-whmcs-plugin";
$local = __DIR__."/../../../../servers/asciossl";
$installer = new Installer($git,$local,$_GET["module"]); 
try {
    if($_GET["action"]=="db") {
        $installer->doDatabaseUpdates();
    } else {
        $installer->doFsUpdates();
    }
    $ret = ["success" => "true"];
} catch (AscioSystemException $e) {
    $ret = ["error" => $e->getMessage()] ;
}
echo json_encode($ret);



