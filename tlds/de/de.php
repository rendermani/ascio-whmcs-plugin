<?php

class de extends Request {	
	public function transferDomain($params) {
		$params["regperiod"] = 0 ;
		parent::transferDomain($params);
	}
	public function setEPPCode($params) {
		parent::setEPPCode(Tools::generateEPPCode(8,"_%&!"));
	}
	protected function mapToRegistrant($params) {
		$contact = parent::mapToRegistrant($params);
		$contact["RegistrantNumber"] = $params["additionalfields"]["Tax ID"];
		return $contact;
	}
	protected function mapToAdmin($params) {
		$contact = parent::mapToAdmin($params);
		if(!$contact["Fax"]) $contact["Fax"] = $contact["Phone"];
		return $contact;
	}
	protected function mapToTech($params) {
		$contact = parent::mapToAdmin($params);
		if(!$contact["Fax"]) $contact["Fax"] = $contact["Phone"];
		return $contact;
	}	
}
?>