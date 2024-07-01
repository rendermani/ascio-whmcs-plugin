<?php

namespace ascio\v2\domains;

class sg extends Request {	
	public function mapToOrder($params,$orderType) {
		$ascioParams = parent::mapToOrder($params,$orderType);
		if($params["additionalfields"]["Local Presence"]=="on"){
			$ascioParams["order"]["LocalPresence"] = "LocalPresenceAdmin";		
		} 
		
		return $ascioParams;
	}
	protected function mapToRegistrant($params) {
		$contact = parent::mapToRegistrant($params);
		$contact["RegistrantNumber"] = $params["additionalfields"]["Registrant ID"];
		return $contact;
	}
	protected function mapToAdmin($params) {
		$contact = parent::mapToAdmin($params);
		$contact["OrganisationNumber"] = $params["additionalfields"]["Admin ID"];
		return $contact;
	}
}
?>