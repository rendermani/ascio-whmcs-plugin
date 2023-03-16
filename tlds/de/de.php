<?php

class de extends Request {	
	public function transferDomain($params=false) {
		return parent::transferDomain($params);
	}
	public function updateEPPCode($params) {
		$characters = "ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789+-/*";
		$params["eppcode"] = Tools::generateEppCode(12, $characters);
		parent::updateEPPCode($params);
		return $params;

	}
	protected function mapToRegistrant($params) {
		$contact = parent::mapToRegistrant($params);
		$regNr1 = $params["additionalfields"]["Tax ID"];
		$regNr2 = $contact["custom"]["RegistrantNumber"];
		$contact["RegistrantNumber"] = $regNr1 ? $regNr1 : $regNr2;
		return $contact;
	}
	protected function mapToAdmin($params) {
		$contact = parent::mapToAdmin($params);
		if(!$contact["custom"]["Fax"]) $contact["Fax"] = $contact["Phone"];
		return $contact;
	}
	protected function mapToTech($params) {
		$contact = parent::mapToAdmin($params);
		if(!$contact["custom"]["Fax"]) $contact["Fax"] = $contact["Phone"];
		return $contact;
	}	
	public function renewDomain($params) {
		$domain = parent::searchDomain($params);
		if($this->hasStatus($domain,"expiring")) {
			return parent::unexpireDomain($params);
		} else return array("error" => "Domain can't be renewed again.");		
	}
}
?>