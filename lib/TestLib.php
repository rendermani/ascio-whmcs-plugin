<?php
// Initialize WHMCS
require_once(realpath(dirname(__FILE__))."/../../../../init.php");;
require_once realpath(dirname(__FILE__))."/../../../../includes/registrarfunctions.php";
require_once("vendor/autoload.php");
require_once("Request.php");

use ascio\v2\domains\Request;
class DomainObject {
    private $idnSecondLevel;
    private $topLevel;
    public function __construct($idnSecondLevel, $topLevel) {
        $this->topLevel = $topLevel;
        $this->idnSecondLevel = $idnSecondLevel;
    }
    /**
     * @return string
     */
    public function getIdnSecondLevel() {
        return $this->idnSecondLevel;
    }
    /**
     * @return string
     */
    public function getTopLevel() {
        return $this->topLevel;
    }
}

class TestLib {
    public static function register($tld, $additionalFields = [], $override = []) {
		// register domains
        $faker = Faker\Factory::create();
        $word = $faker->domainWord ."-whmcs-". time();
        $domainName = $word . "." . $tld;
        $filename = __DIR__ . '/../test/whmcs_8.9_params_register.json';
        $jsonString = file_get_contents($filename);
        if ($jsonString === false) {
            throw new Exception("Failed to read the file: " . $filename);
        } 
        $orderData = json_decode($jsonString, true);
        if ($orderData === null) {
            throw new Exception("Failed to decode JSON. Error: " . json_last_error_msg());
        } 
        $cfg = getRegistrarConfigOptions("ascio");
        $orderData["Username"] = $cfg["Username"];
        $orderData["Password"] = $cfg["Password"];
        $orderData["domainObj"] = new DomainObject($word, $tld);
        $orderData["tld"] = $tld;
        $orderData["additionalfields"] = $additionalFields;
        $orderData["domainname"] = $orderData["domain"] = $orderData["domain_punycode"] = $domainName;
        foreach($override as $key => $value) {
            $orderData[$key] = $value;
        }
        $request = Request::create($orderData);
		$premiumDomainsEnabled = (bool) $orderData['premiumEnabled'];
		$premiumDomainsCost = $orderData['premiumCost'];		
		$orderData = $request->setParams($orderData);
		try {	
            $ascioParams = $request->mapToOrder($orderData,"Register_Domain");
			if ($premiumDomainsEnabled && $premiumDomainsCost) {
				$ascioParams['order']['AgreedPrice'] = $premiumDomainsCost;
			}
		} catch (AscioException $e) {
			return array("error" => $e->getMessage());
		}	
		$result = $request->request("ValidateOrder",$ascioParams);				
		return $result;
	}
    public static function transfer($tld, $additionalFields = [], $override = []) {
		// register domains
        $faker = Faker\Factory::create();
        $word = "ascio-whmcs";
        $domainName = $word . "." . $tld;
        $filename = __DIR__ . '/../test/whmcs_8.9_params_register.json';
        $jsonString = file_get_contents($filename);
        if ($jsonString === false) {
            throw new Exception("Failed to read the file: " . $filename);
        } 
        $orderData = json_decode($jsonString, true);
        if ($orderData === null) {
            throw new Exception("Failed to decode JSON. Error: " . json_last_error_msg());
        } 
        $cfg = getRegistrarConfigOptions("ascio");
        $orderData["eppcode"] = "X4Y7Z9";
        $orderData["Username"] = $cfg["Username"];
        $orderData["Password"] = $cfg["Password"];
        $orderData["domainObj"] = new DomainObject($word, $tld);
        $orderData["tld"] = $tld;
        $orderData["additionalfields"] = $additionalFields;
        $orderData["domainname"] = $orderData["domain"] = $orderData["domain_punycode"] = $domainName;
        foreach($override as $key => $value) {
            $orderData[$key] = $value;
        }
        $request = Request::create($orderData);
		$premiumDomainsEnabled = (bool) $orderData['premiumEnabled'];
		$premiumDomainsCost = $orderData['premiumCost'];		
		$orderData = $request->setParams($orderData);
		try {	
            $ascioParams = $request->mapToOrder($orderData,"Transfer_Domain");
			if ($premiumDomainsEnabled && $premiumDomainsCost) {
				$ascioParams['order']['AgreedPrice'] = $premiumDomainsCost;
			}
		} catch (AscioException $e) {
			return array("error" => $e->getMessage());
		}	
		$result = $request->request("ValidateOrder",$ascioParams);				
		return $result;
	}
    public static function getOrCreateClient ($country = 'US') {
        $postData = array(
            'search' => 'johndoe@example.com',
        );
        $result = localAPI('GetClients', $postData);
        if(!$result["totalresults"]) {
            $command = 'AddClient';
            $postData = array(
                'firstname' => 'John',
                'lastname' => 'Doe',
                'email' => 'johndoe-'.strtolower($country).'@example.com',
                'address1' => '123 Main Street',
                'city' => 'New York',
                'state' => 'NY',
                'postcode' => '10001',
                'country' => $country,
                'phonenumber' => '555-555-5555',
                'password2' => 'U9JdHpsfUzBCbbub7ayT', // Generate a random password, change this!
                'currency' => 1,
            );
            $result = localAPI('AddClient', $postData);
            $clientId = $result["clientid"];
        } else {
            $clientId = $result["clients"]["client"][0]["id"];
        }
        return $clientId;
    }
}
