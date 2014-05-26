<?php
class asia extends Request {	
	public function registerDomain($params=false) {
		$params = $this->setParams($params);
		$ascioParams = $this->mapToOrder($params,"Register_Domain");
		$asianCountries = [
			"AE","AF","AM","AQ","AU","AZ","BD","BH","BN","BT","CC","CK","CN","CV","CX","CY","FJ","FM","GE","GU","HK",
			"HM","ID", "IL","IN","IQ","IR","JO","JP","KG","KH","KI","KP","KR","KW","KZ","LA","LB","LK","MH","MM","MN",
			"MO","MV","MY","NF","NP","NR","NU","NZ","OM","PG","PH","PK","PS","PW","QA","SA","SB","SG","SY","TH","TJ",
			"TK","TL","TM","TO","TR","TV","TW","UZ","VN","VU","WS","YE"];

		if(!in_array($ascioParams["order"]["Domain"]["Registrant"]["CountryCode"],$asianCountries)) {			
			$ascioParams["order"]["LocalPresense"] = "LocalPresenceAdmin ";

			
		}	
		$ascioParams["order"]["Domain"]["DomainPurpose"] = "Registrant";
		Tools::dump($ascioParams,"ok");	
		$result = $this->request("CreateOrder",$ascioParams);
		if (!$result) {
			$this->setWhmcsStatus($domainName,"Pending","Register_Domain");
		}
		return $result;
	}
	protected function mapToRegistrant($params) {
		$contact = parent::mapToRegistrant($params);
		$contact["RegistrantType"]	 = $params["additionalfields"]["Legal Type"];
		$contact["Details"] 		=  $params["additionalfields"]["Identity Form"];
		$contact["RegistrantNumber"] 		=  $params["additionalfields"]["Identity Number"];
		return $contact;
	}
}
?>