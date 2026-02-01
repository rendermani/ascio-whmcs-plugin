<?php
require_once(realpath(dirname(__FILE__))."/../../../init.php");
require_once("lib/Ssl.php");
require_once("lib/Status.php");
require_once("lib/Sans.php");
use Illuminate\Database\Capsule\Manager as Capsule;
use ascio\whmcs\ssl\Ssl;
use ascio\whmcs\ssl\Params;
use ascio\whmcs\ssl\Status;

//$_POST["serviceId"] = 34;

$status = new Status( $_POST["serviceId"],true);
$status->setOrder();
$status->init();
$finished = $status->isFinished();
$status->setTitle("SSL Certificate: " .$status->getName());
$status->setExpireDate();
$status->setActions();
$html = $status->getStatusHtml() . $status->getInstructionsHtml();
$html = '<div class="panel-sidebar panel">'.$html.'</div>';
$params = new Params();
$params->serviceId = $_POST["serviceId"];
$ssl = new Ssl($params);
$sslData = $ssl->readDb();
$sans = $ssl->getSans();
if(count($sans) > 0) foreach($sans->data as $key => $san) {   
    $status->setSanData(array_merge( (array) $sslData,$san));
    $status->init();
     //$html .= "<pre>".print_r((array) $sslData,1)."</pre>";
     //$html .= "<pre>".print_r($san,1)."</pre>";
    $status->setTitle("Additional Name (SAN): " .$san["name"]);
    if($san["dns_created"]==0 && $sslData->status=="Pending_End_User_Action") {
        $html .=  '<div class="panel-sidebar panel">'. $status->getStatusHtml() . $status->getInstructionsHtml().'</div>';
    } else {
        $html .=  '<div class="panel-sidebar panel">'. $status->getStatusHtml() .'</div>';
    }
    
}
echo json_encode(["status" => $html]);
?>