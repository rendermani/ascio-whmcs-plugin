<?php

namespace ascio;

class at extends Request {	
	public function mapToOrder($params, $orderType) { 
		if($orderType == "Transfer_Domain" && $params["regperiod"] == 1) $params["regperiod"] = 0 ;
		return parent::mapToOrder($params, $orderType);
	}		
}
?>