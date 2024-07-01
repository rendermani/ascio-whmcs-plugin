<?php

namespace ascio\v2\domains;

class xxx extends Request {	
	public function registerDomain($params=false) {
		$params = $this->setParams($params);
		$ascioParams = $this->mapToOrder($params,"Register_Domain");
		
		if($params["additionalfields"]["Member of sponsored community"] == "on") {
			$ascioParams["order"]["Options"] = "member";
		} else {
			$ascioParams["order"]["Options"] = "non-member";
		}		
		$result = $this->request("CreateOrder",$ascioParams);
		if (!$result) {
			$this->setWhmcsStatus($domainName,"Pending","Register_Domain");
		}
		return $result;
	}
}
?>