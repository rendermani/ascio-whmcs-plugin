<?php

namespace ascio\v2\domains;

class com_my extends Request {	
	public function mapToOrder($params,$orderType) {
		$ascioParams = parent::mapToOrder($params,$orderType);
		if($params["additionalfields"]["Local Presence"]=="on"){
			$ascioParams["order"]["LocalPresence"] = "LocalPresenceRegistrant";		
		} 
		
		return $ascioParams;
	}
	protected function mapToRegistrant($params) {
		$contact = parent::mapToRegistrant($params);
		$contact["RegistrantNumber"] = $params["additionalfields"]["Registrant ID"];
		return $contact;
	}
}
?>