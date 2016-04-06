<?php
class ch extends Request {
	public function transferDomain($params=false) {
		$params["regperiod"] = 0 ;
		return parent::transferDomain($params);
	}		
	public function renewDomain($params) {
		$domain = parent::searchDomain($params);
		if($this->hasStatus($domain,"expiring")) {
			return parent::unexpireDomain($params);
		} else return array("error" => "Domain can't be renewed again.");		
	}
}
?>