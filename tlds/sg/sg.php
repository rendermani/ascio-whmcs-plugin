<?php
class sg extends Request {	
	protected function mapToRegistrant($params) {
		$contact = parent::mapToRegistrant($params);
		$contact["RegistrantNumber"] = $params["additionalfields"]["Registrant ID"];
		return $contact;
	}
}
?>