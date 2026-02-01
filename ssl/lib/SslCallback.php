<?php
namespace ascio\whmcs\ssl;
require("Callback.php");
require("Ssl.php");
use Illuminate\Database\Capsule\Manager as Capsule;
use ascio\v3 as v3;

class SslCallback extends Callback {
    protected $authName = false;
    protected $authValue = false;
    protected $authType;
    protected $certificateId;
    /**
     * @var v3\SslCertificate
     */
    protected $certificate; 
    /**
     * @var Ssl $ssl
     */
    protected $ssl;
    public function process($orderId,$status,$messageId,$message = null) {

        parent::process($orderId,$status,$messageId,$message);          
        $this->ssl = new Ssl($this->params);
        $this->ssl->fqdn = $this->fqdn;
        $this->ssl->readDb();
        $this->processMessage();
        
        if($this->status =="Completed") {
            $certificateHandle = $this->order->getOrderRequest()->getSslCertificate()->getHandle();
            $this->certificate = $this->ssl->getCertificate($certificateHandle);
            $this->data["certificate_id"] = $certificateHandle;
            $this->data["expire_date"] = $this->certificate->getExpires();
        }    
        $this->writeStatus();
        parent::ack();       
    } 
    protected function writeCertificateData() {
        Capsule::table("mod_asciossl")
        ->where(["whmcs_service_id" => $this->serviceId])
        ->update(["certificate_id" => $this->certificate->getHandle()]);
    } 
    protected function processMessage()
    {
        if($this->status =="Pending_End_User_Action") {
            if($this->validForDns()) {
                $data = [];
                try {
                    $data = $this->ssl->createDns($this->authName, $this->authValue);
                    $this->data["dns_created"] = true;
                    $this->data["dns_error_code"] = "";
                    $this->data["dns_error_message"] = "";
                    $this->createSanDns();
                    
                } catch (AscioUserException $e) {
                    logModuleCall("asciossl", "Register SSL", $this->data, $e, json_encode($e));   
                    echo "DNS Creation Error: ". $e->getMessage()."\n";
                    $this->data["dns_error_code"] = $e->getTemplateCode();
                    $this->data["dns_error_message"] = $e->getMessage();
                }                
            }
        }
        if($this->status=="Failed" || $this->status=="Invalid") { 
            $message = $this->getMessage($this->messageId);
            $this->data["message"] = $message->getMessage();
        }
    }
    private function createSanDns() {
        $sans = $this->ssl->getSans();
        $data = [];
        foreach($sans->data as $key => $san) {
            try {
                $fqdn = new Fqdn($san["name"]);                
                $this->ssl->createDns($san["dns_name"], $san["dns_value"],$fqdn);
                $san["dns_created"] = true;
                $san["dns_error_code"] = "";
                $san["dns_error_message"] = "";
                $san["verification_type"] = $this->ssl->data["verification_type"];
            } catch (AscioUserException $e) {
                logModuleCall("asciossl", "Create DNS", $san, $e, json_encode($e));    
                echo "DNS Creation Error: ". $e->getMessage()."\n";
                $san["dns_error_code"] = $e->getTemplateCode();
                $san['dns_error_message'] = $e->getMessage();
            } 
            $data[] = $san;
        }
        $sans->data = $data;     
        $sans->writeDb();
        
    }
    

    private function validForDns() {
        $this->parseDnsToken();
        $this->parseFile();
        if($this->authValue) {
            Capsule::table("mod_asciossl") 
            ->where("whmcs_service_id",$this->serviceId)
            ->update([
                "dns_name" => $this->authName,
                "dns_value" => $this->authValue
            ]);
        }
        if(
            $this->authValue && 
            $this->authType !== "file"
        ) {
            return true;
        } else return false;
    }
    private function parseDnsToken() {
        $regex = '/AuthName: (.*)\nAuthValue: (.*)/';
        preg_match($regex,$this->message,$result);
        
        if(count($result) < 1) return;
        
        $this->authName = trim($result[1]);
        $this->authValue = trim($result[2]);
        $this->authType = "dns";
    }
    private function parseFile() {
        $regex = '/AuthFileName: (.*)\nAuthFileContent: (.*)/';
        preg_match($regex,$this->message,$result);    

        if(count($result) < 1) return;

        $this->authName = trim($result[1]);
        $this->authValue = trim($result[2]);
        $this->authType = "file";
    }
}
