<?php

class nl extends Request {	
	protected function mapToRegistrant($params) {
		$contact = parent::mapToRegistrant($params);
		if($contact["OrgName"]) {
			$contact["RegistrantNumber"] = $params["additionalfields"]["Organization Number"];
			if(!$contact["RegistrantNumber"]) $contact["RegistrantNumber"] = "123123123";
		}
		return $contact;
	}

}
?>