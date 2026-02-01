<?php
namespace ascio\whmcs\ssl;
use Illuminate\Database\Capsule\Manager as Capsule; 
class Params {
    private $whmcsParamameters;
    public $serviceId; 
    public $userId; 
    public $certificateType; 
    public $account;
    public $password; 
    public $testAccount;
    public $testPassword;     
    public $testmode;
    public $createDnsRecord;
    public $requireDomain;
    public $paidSans;
    public $settings; 
    
    public function __construct ($whmcsParameters=null) {
        $settings = $this->getDbData();

        $this->account = $settings->Account;
        $this->password = $settings->Password;
        $this->testAccount = $settings->AccountTesting;
        $this->testPassword = $settings->PasswordTesting;
        $this->testmode = $settings->Environment ==  'testing' ? true : false;
        $this->createDnsRecord = $settings->CreateDns;
        $this->requireDomain = $settings->RequireDomain;
        $this->settings = $settings;

        if(!$whmcsParameters) return;

        $this->whmcsParamameters = $whmcsParameters; 
        $this->serviceId = $whmcsParameters["serviceid"];        
        $this->userId = $whmcsParameters["userid"]; 
        $this->certificateType = $whmcsParameters["configoption1"];
        $this->paidSans = reset($whmcsParameters["configoptions"]);      
   
        
    }
    private function getDbData() {
        $table = Capsule::table("mod_asciossl_settings");
        $settings = [];
        foreach($table->get() as $key => $row) {
            $settings[$row->name] = $row->value;
        }
        $this->settings =  (object) $settings;
        return $this->settings;
    }
    public function getData () {
        return [
            "whmcs_service_id" => $this->serviceId,
            "user_id" => $this->userId,
            "type" => $this->certificateType , 
            "create_dns_record" => $this->createDnsRecord
            
        ];
    }
    public function getCredentials($forceLive = false) {
        if($forceLive || $this->testmode == false) {            
            return [               
                "Account"=> $this->account, 
                "Password" => $this->password
            ];
        } else {
            return [
                "Account"=> $this->testAccount, 
                "Password" => $this->testPassword
            ]; 
        }                
    }
    public function getWsdlV2($forceLive = false) {
        if($forceLive || $this->testmode == false) {
            $prefix = "";
        } else {
            $prefix = "demo.";
        }
        return "https://aws.".$prefix."ascio.com/2012/01/01/AscioService.wsdl";
    }
    public function getWsdlV3($forceLive = false) {
        if($forceLive || $this->testmode == false) {
            $prefix = "";
        } else {
            $prefix = "demo.";
        }
        return "https://aws.".$prefix."ascio.com/v3/aws.wsdl";
    }
    public function setAccount($account,$password) {
        if($this->testmode) {
            $this->testPassword = $password;
            $this->testAccount = $account; 
        } else {
            $this->password = $password;
            $this->account = $account; 
        }
    }
}
