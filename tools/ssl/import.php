<?php
require_once(realpath(dirname(__FILE__))."/../../../../init.php");
require_once("ProductImporter.php");


header('Content-Type: application/json');
if($_SESSION["adminid"] < 1) {
    echo json_encode(["error" => "Invalid Session"] );
    die();
}

use ascio\whmcs\ssl\Ssl;
use ascio\whmcs\ssl\Params;
use ascio\whmcs\ssl\Fqdn;
use ascio\whmcs\ssl\ProductImporter; 


$pi = new ProductImporter();
$pi->readCSV(__DIR__."/../import/products.csv");
$pi->setMargin($_GET["margin"]);
$pi->setRoundStep($_GET["round"]);
$pi->setProducts($_GET["products"]);

if($_GET["action"]=="import") {
    $pi->import();
}
echo json_encode(["html" => $pi->preview()]);
