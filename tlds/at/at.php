<?php

namespace ascio\v2\domains;

class at extends Request {	
	public function mapToOrder($params=false, $orderType) { 
		if($orderType == "Transfer_Domain" && $params["regperiod"] == 1) $params["regperiod"] = 0 ;
		return parent::mapToOrder($params, $orderType);
	}		
}
?>