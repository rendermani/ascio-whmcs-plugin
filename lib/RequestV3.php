<?php
use Illuminate\Database\Capsule\Manager as Capsule;
use ascio\whmcs\ssl as ssl; 
use WHMCS\Domain\Registrar\Domain;
require_once("Tools.php");
require_once("ParameterCapture.php");
define("ASCIO_V3_WSDL_LIVE","https://aws.ascio.com/v3/aws.wsdl");
define("ASCIO_V3_WSDL_TEST","https://aws.demo.ascio.com/v3/aws.wsdl");

Class RequestV3 {
	var $account;
	var $password; 
	var $params;
	var $domain;
	public $domainName;
	public function __construct($params) {
		$this->setParams($params);
	}
	public function setParams($params) {
		if($params) {
			$this->params = $params; 			
			if($this->params["Username"]) $this->account = $this->params["Username"];
			if($this->params["Password"]) $this->password = $this->params["Password"];
			if(isset( $params["domainObj"]) && isset( $params["sld"])) {
				$this->domainName = $params["domainObj"]->getIdnSecondLevel().".".$params["domainObj"]->getTopLevel();		
			} else {
				$this->domainName = $params["domainName"];
			}
		} 
		return $this->params;
	}
	private function sendRequest($functionName,$ascioParams) {			
		$wsdl = $this->params["TestMode"]=="on" ? ASCIO_V3_WSDL_TEST : ASCIO_V3_WSDL_LIVE;        
		$client = new SoapClient($wsdl,array( "cache_wsdl " => WSDL_CACHE_MEMORY, "trace" => 1 ));
		$credentials = ["Account"=> $this->account, "Password" => $this->password];
        $header = new \SoapHeader("http://www.ascio.com/2013/02","SecurityHeaderDetails", $credentials, false);
		$client->__setSoapHeaders($header);
		$response = $client->__soapCall($functionName, array('parameters' => ["request" => $ascioParams ] ));   
		$resultName = $functionName . "Result";	
		$result = $response->$resultName;
		Tools::logModule($functionName,$ascioParams,$result);
		if ( $result->ResultCode == 200 ||$result->ResultCode == 201 || $result->ResultCode == 413 ) {			
			return $result;
		} else if( $result->ResultCode==554)  {
			$messages = "Temporary error. Please try later or contact your support.";
		} elseif ($result->ResultCode==401) {
			logActivity("Ascio registrar plugin settings - Login failed, invalid account or password: ".$this->account);
			return array('error' => $result->ResultMessage );     
		} else if (is_array($result->Errors->string) && count($result->Errors->string) > 1 ){
			$messages = join(", \r\n",$result->Errors->string);	
		}  else {
			$messages = $result->Errors->string;
		}		
		$message = Tools::cleanString($messages);
		return array('error' => $message );     
	}
	/**
	 * 
	 * @param mixed $ascioParams 
	 * @return object 
	 */
	public function getPrices($ascioParams) {
		return $this->sendRequest("GetPrices", $ascioParams);
	}

}
?>