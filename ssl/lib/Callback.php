<?php
namespace ascio\whmcs\ssl; 

require_once(__DIR__."/../v3/service/autoload.php");
require_once("Error.php");

use ascio\v3 as v3;
use Illuminate\Database\Capsule\Manager as Capsule;


class Callback {
    public $order;
    protected $status; 
    protected $orderId;     
    protected $serviceId;
    protected $messageId;
    protected $message;
    protected $transactionComment;
    protected $resultCode;
    protected $resultMessage;
    protected $data=[];
    /**
    * @var Params $params;  
    */
    protected $params; 
    /**
     * @var Fqdn $fqdn
     */
    public $fqdn; 
    public $module;
    function __construct(Params $params,$orderId) {        
        $this->params = $params;
        $header = new \SoapHeader("http://www.ascio.com/2013/02","SecurityHeaderDetails", $params->getCredentials(), false);
        $this->client = new v3\AscioService(array("trace" => true),$params->getWsdlV3());
        $this->client->__setSoapHeaders($header);
        if(intval($orderId) > 0) {
            $this->orderId = $params->testmode ? "TEST".$orderId : "A".$orderId; 
        } else {
            $this->orderId = $orderId;
        }     
       $this->getServiceData();   
    }
    public function process($orderId,$status,$messageId,$message = null) {
        $this->status = $status; 
        $this->getMessage($messageId,$message); 
        // process status       
        if (
            $this->status =="Failed"|| 
            $this->status =="Invalid"|| 
            $this->status =="Completed" ||
            $this->status =="Order not validated" ||
            $this->status =="Pending_End_User_Action" 
            ) {
                $this->getOrder();
        }
        $this->data["status"]  = $this->status; 
    }
    public function writeStatus($whmcsStatus=null){
        try {
            $result = Capsule::table("mod_asciossl")
            ->where("order_id",$this->orderId)
            ->update($this->data); 
        } catch (\Exception $e){
            throw new AscioSystemException("Error updating status: {$e->getMessage()}");
        }        
        $this->setWhmcsStatus($whmcsStatus);
    }
    public function setWhmcsStatus($status=null) {
        $status = $status ? $status : $this->status;
        switch($status) {
            case "Completed" : $whcmsStatus = "Active"; break;
            default : $whcmsStatus = "Pending";
        }
        $data = array(
            'serviceid' => $this->serviceId,
            'status' => $whcmsStatus
        );
        $result = localAPI('UpdateClientProduct', $data);
        if(!$result["result"]=="success") {
            throw new AscioSystemException($result["error"]);
        }
    }
    public function ack() {
        $request =  new v3\AckQueueMessageRequest();
        $request->setMessageId($this->messageId);        
        $result = $this->client->AckQueueMessage(new v3\AckQueueMessage($request));
        if($result->AckQueueMessageResult->getResultCode() !== 200) {
             throw new AscioSystemException(
                 $result->AckQueueMessageResult->getResultMessage(). " (".$this->messageId.")",
                 $result->AckQueueMessageResult->getResultCode(),
                 $request
                );
        }

    }
    private function getServiceData() {
        $data = Capsule::Table("mod_asciossl")
        ->select(["whmcs_service_id","user_id","module"])
        ->where("order_id", $this->orderId)
        ->first();
        $this->serviceId = $data->whmcs_service_id;        
        $this->userId = $data->user_id;
        $this->module = $data->module; 
        $this->params->serviceId = $this->serviceId; 
        $this->params->userId = $this->userId; 
    }

    private function getOrder() {
        $request =  new v3\GetOrderRequest();
        $request->setOrderId($this->orderId);          
        try {
            $response = $this->client->GetOrder(new v3\GetOrder($request));                
        } catch (\Exception $e) {
            throw new AscioSystemException($e->faultcode,$e->faultstring);        
        }            
        $result = $response->GetOrderResult;
        if(!$result->getOrderInfo()) {
            throw new Exception($result->getResultMessage());
        }   
        $this->fqdn  = new Fqdn($result->getOrderInfo()->getOrderRequest()->getSslCertificate()->getCommonName());
        $this->order = $result->getOrderInfo();       
    }
    public function getMessage($messageId,$message=null) {
        $this->messageId = $messageId;
        if($message) {
            $this->message = $message;
            return; 
        }
        $request =  new v3\GetQueueMessageRequest();
        $request->setMessageId($this->messageId);
        try {            
             $response = $this->client->GetQueueMessage(new v3\GetQueueMessage($request));
        } catch (\Exception $e) {
                throw new AscioSystemException($e->faultcode,$e->faultstring);                
        }
        $this->message = $response->GetQueueMessageResult->getMessage();
        return $this->message;
    }
}


