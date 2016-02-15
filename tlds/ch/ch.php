<?php
class ch extends Request {		
	public function renewDomain($params) {
		$domain = parent::searchDomain($params);
		if($this->hasStatus($domain,"expiring")) {
			return parent::unexpireDomain($params);
		} else return array("error" => "Domain can't be renewed again.");		
	}
}
?>