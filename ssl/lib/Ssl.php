<?php
namespace ascio\whmcs\ssl;
require_once(__DIR__."/../v3/service/autoload.php");
require_once(__DIR__."/Fqdn.php");
require_once(__DIR__."/Dns.php");
require_once(__DIR__."/Domain.php");
require_once(__DIR__."/Error.php");
require_once(__DIR__."/Status.php");
require_once(__DIR__."/Sans.php");

use Illuminate\Database\Capsule\Manager as Capsule;
use ascio\v3 as v3;
use ascio\ssl\CertificateConfig;
use ascio\ssl\CertConfig;

class Ssl {
    public $data =[];
    /**
     * @var v3\AscioService $client
     */
    protected $client; 
    protected $clientId; 
    public $serviceId;
    protected $testmode; 
    /**
     * @var Fqdn $fqdn Full qualified domain name
     */
    public $fqdn;
    /**
     * var Params $params
     */
    public $params; 
    private $orderId;
    private $account;
    private $password;
    public $certificateType;
    /**
     * 
     * @var string $verificationType Dns, File, Email
     */
    public $verificationType;
    /**
     * @var boolean $hasDbData was readDb already executed
     */
    public $hasDbData = false;
    /**
     * @var Sans $sans SAN Management
     */
    protected $sans;
    public $dnsName;
    public $dnsValue; 
    public function __construct(Params $params)
    {        
        $this->sans = new Sans($this);
        $this->params = $params; 
        
        $this->data = $params->getData();
        if(!isset($params->account) && !isset($params->testAccount)) return;
                
        $this->serviceId = $params->serviceId;
        $this->certificateType =$params->certificateType;
        $header = new \SoapHeader("http://www.ascio.com/2013/02","SecurityHeaderDetails", $params->getCredentials(), false);
        $this->client = new v3\AscioService(array("trace" => true),$params->getWsdlV3());
        $this->client->__setSoapHeaders($header);
        
    }
    public function getSans() : Sans {
        return $this->sans;
    }
    public function fromForm() {
        global $_POST;
        $data = [
            "csr" => $_POST["csr"],
            "verification_type" => $_POST["verificationType"],
            "create_dns_record" => $_POST["createDns"],
            "approval_email" => $_POST["approvalEmail"],
            "webserver" => $_POST["webserver"],
            "common_name" => $_POST["commonName"]
        ];
        $this->data = array_merge($data,$this->data);
        $this->sans->fromForm();
        return $this->data;        
    }
    public function toForm() {                
        if(!$this->data["csr"]) {
            $data = (array) $this->readDb();
            $data = array_merge($this->sans->toHtml(),$data);
            return $data;
        }
        return (array) $this->data;
    }
    public function writeDb() { 
        $this->readDb();   
        if($this->hasDbData) {
            Capsule::table('mod_asciossl')
            ->where("whmcs_service_id",$this->serviceId)
            ->update($this->data);
        } else {
            Capsule::table('mod_asciossl')->insert($this->data);
        }
        if($this->data["common_name"]) {
            Capsule::table('tblhosting')
            ->where("id",$this->serviceId)
            ->update(["domain"=> $this->data["common_name"]]);
        }
        $this->sans->writeDb();
    }
    public function readDb() {
        /**
         * select mod_asciossl.*, tblhosting.nextduedate, tblhosting.billingcycle from mod_asciossl left JOIN tblhosting on tblhosting.id = mod_asciossl.whmcs_service_id where mod_asciossl.whmcs_service_id = 5
         */
        $data = Capsule::table('mod_asciossl')
        ->select('mod_asciossl.*','tblhosting.nextduedate', 'tblhosting.billingcycle as period')
        ->join("tblhosting","tblhosting.id","=","mod_asciossl.whmcs_service_id")
        ->where('mod_asciossl.whmcs_service_id',$this->serviceId)
        ->first();      
        if(isset($data)) {       
            $this->hasDbData = true; 
            $this->fqdn = new Fqdn($data->common_name);
            switch($data->period) {
                case "Annually" : $data->period = 1; break;
                case "Biennial" : $data->period = 2; break;
                case "Triennial" : $data->period = 3; break;
                default : $data->period = 1; 
            }
            $this->verificationType = $data->verification_type; 
            $this->dnsName = $data->dns_name;
            $this->dnsValue = $data->dns_value;
            $this->sans->readDb();
        }
        $data->errors = \json_decode($data->errors);
        //$this->data = array_merge((array) $data, $this->data);
        return $data;
    }
    function register($contacts) : array {
        return $this->submit($contacts,v3\OrderType::Register);
    }
    function renew($contacts) : array {
        return $this->submit($contacts,v3\OrderType::Renew);
    }
    function reissue($contacts) : array {
        return $this->submit($contacts,v3\OrderType::DetailsUpdate);
    }
    function submit($contacts, $orderType) : array { 
        $data = $this->readDb();
        $owner =  new v3\Registrant();
        $contacts->setRequestFields($owner,"owner");    
        $admin =  new v3\Contact();
        $contacts->setRequestFields($admin,"admin");    
        $tech =  new v3\Contact();
        $contacts->setRequestFields($tech,"tech");
    
        $sslCertificate =  new v3\SslCertificate();
        if($orderType==v3\OrderType::DetailsUpdate) {
            $sslCertificate->setHandle($data->certificate_id);
        } else {
            $sslCertificate->setCommonName($data->common_name);
        }
        $data->approval_email = $data->verification_type == "Email" ? $data->approval_email : "admin@".$data->common_name;
        $sslCertificate->setProductCode($data->type);
        $sslCertificate->setWebServerType( $data->webserver);
        $sslCertificate->setApproverEmail($data->approval_email. $this->sans->getApprovalAddresses());
        $sslCertificate->setCSR($data->csr);
        $sslCertificate->setOwner($owner);
        $sslCertificate->setAdmin($admin);
        $sslCertificate->setTech($tech);       
        $sslCertificate->setValidationType($data->verification_type);
        $sslCertificate->setSanNames($this->sans->getArray());
       
        $request =  new v3\SslCertificateOrderRequest();
        $request->setType($orderType);
        $request->setPeriod($data->period);
        $request->setTransactionComment("WHMCS SSL Module");
        $request->setSslCertificate($sslCertificate);
        try {
            /**
             * @var v3\CreateOrderResult $createOrderResponse 
             */
            $createOrderResponse = $this->client->CreateOrder(new v3\CreateOrder($request));
        } catch (\Exception $e) {
            logModuleCall("asciossl", "Register SSL", $request, $e, json_encode($e));         
            return ["error" => "Temporary error. Please retry later"];
        }

        if ($createOrderResponse->CreateOrderResult->getResultCode() == 200) {
            $result = [
                "code"  => $createOrderResponse->CreateOrderResult->getResultCode(),
                "message" => $createOrderResponse->CreateOrderResult->getResultMessage(),
                "status" => $createOrderResponse->CreateOrderResult->getResultMessage(), 
                "order_id" => $createOrderResponse->CreateOrderResult->getOrderInfo()->getOrderId(),
                "status" => $createOrderResponse->CreateOrderResult->getOrderInfo()->getStatus(),
                "errors" => null
            ];
            $this->writeStatus($result);

        } else {
            $result = [
                "code"  => $createOrderResponse->CreateOrderResult->getResultCode(),
                "message" => $createOrderResponse->CreateOrderResult->getResultMessage(), 
                "status" => $createOrderResponse->CreateOrderResult->getResultMessage(), 
                "errors" => json_encode($createOrderResponse->CreateOrderResult->getErrors()->getString())           
            ];
            $this->writeStatus($result);
        }
        return $result;
       
    }
    public function getDownloadLink() {
        //TODO: Create Link
        return $this->serviceId;
    }
    public function getCertificateConfig() : CertConfig {
        $config = new CertificateConfig();
        $cert = $config->get($this->data["type"]);   
        return $cert; 
    }
    /**
     * @param string $certificateHandle 
     * @return  v3\SslCertificate
     */
    public function getCertificate(string $certificateHandle) : v3\SslCertificateInfo {
        $request =  new v3\GetSslCertificateRequest();
	    $request->setHandle($certificateHandle);
	    try {
		    $response = $this->client->GetSslCertificate(new v3\GetSslCertificate($request));
        } catch (\Exception $e) {
            throw  new AscioSystemException($e->faultstring, $e->faultcode);    
        }
        $result = $response->GetSslCertificateResult;
        $cert = $result->getSslCertificateInfo();
        return $cert;
    }
    private function writeStatus($data) {
        Capsule::Table("mod_asciossl")
        ->where("whmcs_service_id",$this->serviceId)
        ->update($data);
    }
    public function createDns($name,$value,$fqdn = null) {
        if(!$fqdn) $fqdn = $this->fqdn;
        $this->data = (array) $this->readDb();
        $domain = Capsule::Table("tbldomains")
        ->select("domain")
        ->where(["domain" => $fqdn->getDomain(), "userid" => $this->data["user_id"]])
        ->where("status", "Active")
        ->first();


        if(!isset($domain) && $this->params->requireDomain) {
            //TODO: test with live account. Doesn't work
            //       throw new AscioUserException("Domain '".$fqdn->getDomain()."' not found in your Account. ",404,"no_ns_domain");
        }
        
        $domain = new Domain($fqdn->getDomain());
        $domain->login($this->params);
        
        $result = $domain->hasAscioDns(); 
        
        // also check $result of domain
        if(isset($name) && isset($value) && $this->data["create_dns_record"] == 1)  {
            $dns = new Dns($this->params,$fqdn);
            $dns->addVerificationRecord($name,$value);             
            return ["dns_created" => true];
        } else {          
            throw new AscioUserException("Auto create DNS not active",400,"noautocreatedns");
        }
    }   
    public function statusHtml()  {
        $status = new Status($this->serviceId);
        $html = $status->getStatusHtml() . $status->getInstructionsHtml();
        return $html;
    }
    public function getApprovalAddresses (Fqdn $fqdn) {
        $fqdnAddresses = $this->getFqdnAddresses($fqdn->getFqdn());
        $domainAddresses = $this->getFqdnAddresses($fqdn->getDomain());
        $whoisAddresses = $this->getWhoisAddresses($fqdn->getDomain());
        $addresses = array_unique(array_merge($whoisAddresses,$fqdnAddresses,$domainAddresses));
        $out = "";
        foreach($addresses as $key => $value) {
            $out .= "<option>".$value."</option>";
        }
        return $out; 
    }
    private function getFqdnAddresses($domain) {
        if(!dns_get_record($domain,DNS_MX)) {
            return [];
        } 
        $names = [
            "admin@".$domain,
            "administrator@".$domain,   
            "hostmaster@".$domain,
            "postmaster@".$domain,
            "webmaster@".$domain
        ];
        return $names;
    }
    private function getWhoisAddresses($domain) {
        exec("whois ".$domain,$result);
        if(!isset($result[0])) return [];
        //echo(join($result,"\n"));
        preg_match_all("/([a-zA-Z0-9]+@[a-zA-Z]+\.[a-zA-Z]*)/",join($result,"\n"),$matches);
        //echo($results["whois"]);
        return (array_unique($matches[0]));
    }
}