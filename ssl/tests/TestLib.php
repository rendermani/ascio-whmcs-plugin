<?php
namespace ascio\whmcs\ssl\test; 
require_once(__DIR__."/../../../../init.php");
require_once(__DIR__."/../asciossl.php");
require_once(__DIR__."/../v3/service/autoload.php");
require_once(__DIR__."/../lib/Fqdn.php");
require_once(__DIR__."/../lib/Dns.php");
require_once(__DIR__."/../lib/Domain.php");
require_once(__DIR__."/../lib/Error.php");
require_once(__DIR__."/../lib/Status.php");
require_once(__DIR__."/../lib/Params.php");

use Illuminate\Database\Capsule\Manager as Capsule;
use ascio\whmcs\ssl as ssl; 

class TestLib {
    public $fqdn; 
    private $account;
    private $password; 
    private $userId; 
    private $packageId = 1; 
    public $serviceId;
    public $orderId; 
    private $certificateType="positivessl";
    private $customFields = [];
    private $options = [];
    private $createDns=true; 
    private $csr;
    /**
     * @var ssl\Params $params
     */
    private $params;
    public function __construct($userId,$domain)
    {
        $this->params = new ssl\Params();
        $this->account = $this->params->testAccount;
        $this->password= $this->params->testPassword;
        $this->userId = $userId; 
        $this->fqdn = new ssl\Fqdn($domain);
    }
    public function setPackageId (int $id) {
        $this->packageId = $id; 
    }
    public function setCertificateType(string $type) {
        $this->certificateType = $type;
        $product = Capsule::table("tblproducts")
        ->where(["configoption1"=>$type])
        ->first();
        $this->packageId = $product->id;
    }
    public function setOptions(array $options) {
        $this->options = $options;
    }
    public function setCustomFields(array $customFields) {
        $this->customFields = $customFields;
    }
    public function setCreateDns(boolean $active) {
        $this->createDns = $active ? "on" : "off";
    }
    private function getCSR() {        
            $dn = array(
                "countryName" => "DE",
                "localityName" => "Munich",
                "stateOrProvinceName" => "Bavaria",
                "organizationName" => "Ascio",
                "commonName" => $this->fqdn->getFqdn(),
                "emailAddress" => "admin@".$this->fqdn->getFqdn()
            );
            $privkey = openssl_pkey_new(array(
                "private_key_bits" => 2048,
                "private_key_type" => OPENSSL_KEYTYPE_RSA,
            ));
            $csr = openssl_csr_new($dn, $privkey, array('digest_alg' => 'sha256'));
            openssl_csr_export($csr, $csrout);
            $this->csr = $csrout;
            openssl_pkey_export($privkey, $pkeyout);
            $this->privateKey = $pkeyout;
            return $this->csr;
        
        
    }
    public function submitCsr ($verificationType="Dns", $sans=false) {
        global $_POST; 
        $results = WhmcsLib::createOrder($this->packageId);        
        $this->serviceId =  $results["productids"];
        $this->orderId =  $results["orderid"];
        $sanAddresses =  
        [
           'admin@'.$this->fqdn->getDomain(),
           'admin@'.$this->fqdn->getDomain(),
           'admin@'.$this->fqdn->getDomain(),
           'admin@'.$this->fqdn->getDomain(),
           'admin@'.$this->fqdn->getDomain()
        ];
        if($sans) {
            $sanDomains = [
                'san-1-'.$this->fqdn->getFqdn(),
                'san-2-'.$this->fqdn->getFqdn(),
                'san-3-'.$this->fqdn->getFqdn(),
                'san-4-'.$this->fqdn->getFqdn(),
                'san-5-'.$this->fqdn->getFqdn()     
            ];
        } else {
            $sanDomains = [];
        }
        $_POST = [
            'submit' =>  'true',
            'step' =>  'contacts',
            'id' =>  '8',
            'commonName' =>  $this->fqdn->getFqdn(),
            'csr' => $this->getCSR(),
            'verificationType' =>  $verificationType,
            'approvalEmail' =>  'admin@'.$this->fqdn->getDomain(),
            'webserver' =>  'ApacheSsl',
            'San' => $sanDomains,
            'SanEmail' => $sanAddresses,
            'save' =>  'Save Changes'
        ];
        $params =  $this->getParams();  
        return asciossl_ClientArea($params);   
    }
    public function submitContacts() {
        global $_POST;
        $_POST = [
            'submit' =>  'true',
            'step' =>  'register',
            'random' =>  uniqid(),
            'id' =>  $this->serviceId,
            'selectedId' =>  'admin',
            'ownerTitle' =>  'mr',
            'ownerFirstName' =>  'Render',
            'ownerLastName' =>  'Mani',
            'ownerCompanyName' =>  'Ascio',
            'ownerEmail' =>  'admin@'.$this->fqdn->getFqdn(),
            'country-calling-code-phonenumberowner' =>  '49',
            'phonenumberowner' =>  '89 7895',
            'ownerPhonePrefix' =>  '+49',
            'ownerAddress1' =>  'teststr.1',
            'ownerAddress2' =>  '',
            'ownerCity' =>  'Munich',
            'ownerState' =>  'Bayern',
            'ownerPostcode' =>  '88888',
            'ownerCountry' =>  'DE',
            'adminTitle' =>  'mr',
            'adminFirstName' =>  'Render',
            'adminLastName' =>  'Mani',
            'adminCompanyName' =>  'Ascio',
            'adminEmail' =>  'admin@'.$this->fqdn->getFqdn(),
            'phonenumberadmin' =>  '89 7895',
            'adminPhonePrefix' =>  '+49',
            'adminAddress1' =>  'teststr.1',
            'adminAddress2' =>  '',
            'adminCity' =>  'Munich',
            'adminState' =>  'Bayern',
            'adminPostcode' =>  '88888',
            'adminCountry' =>  'DE',
            'techTitle' =>  'mr',
            'techFirstName' =>  'Render',
            'techLastName' =>  'Mani',
            'techCompanyName' =>  'Ascio',
            'techEmail' =>  'admin@'.$this->fqdn->getFqdn(),
            'phonenumbertech' =>  '89 7895',
            'techPhonePrefix' =>  '+49',
            'techAddress1' =>  'teststr.1',
            'techAddress2' =>  '',
            'techCity' =>  'Munich',
            'techState' =>  'Bayern',
            'techPostcode' =>  '88888',
            'techCountry' =>  'DE',
            'save' =>  'Save Changes'
        ];
        $params =  $this->getParams();    
        return asciossl_ClientArea($params);   
    }
    private function getParams() {
        $params = [
            'accountid' => 8,
            'serviceid' => $this->serviceId,
            'addonId' => 0,
            'userid' => $this->userId,
            'packageid' => $this->packageId,
            'pid' => $this->packageId,
            'serverid' => 0,
            'status' =>  'Pending',
            'type' =>  'other',
            'producttype' =>  'other',
            'moduletype' =>  'asciossl',
            'configoption1' =>  $this->certificateType,
            'customfields' => $this->customFields,
            'configoptions' => $this->options
        ];
        return $params; 
    }
}
class WhmcsLib {
    public static function createOrder($packageId,$options=[],$customFields=[]) {
        $command = 'AddOrder';        
        $postData = array(
            'clientid' => '1',
            'pid' => $packageId,
            'billingcycle' => array('annually'),
            'paymentmethod' => "banktransfer",
            'customfields' => WhmcsLib::serializeFields($customFields),
            'configoptions' => WhmcsLib::serializeFields($options),
            'regperiod' => 1,
        );
        $results = localAPI($command, $postData);
        return $results; 
    }
    public static function serializeFields($fields) {
        $outFields = [];
        foreach($fields as $key => $field){
            $outFields[] = base64_decode(serialize([$key => $value]));
        }
        return $outFields;
    }
    public function getOrder($serviceId) {

    }
    public function deleteOrder($orderId,$serviceId) {
        $command = 'CancelOrder';
        $postData = array(
            'orderid' => $orderId,
        );
        $results = localAPI($command, $postData);
        $command = 'DeleteOrder';
        $postData = array(
            'orderid' => $orderId,
        );
        $results = localAPI($command, $postData);
        Capsule::table("mod_asciossl")
        ->where("whmcs_service_id",$orderId)
        ->delete();
    }
    public function getProduct($serviceId) {

    }

}
