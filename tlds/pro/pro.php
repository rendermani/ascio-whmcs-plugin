<?php

namespace ascio\v2\domains;

class pro extends Request {	
	protected function mapToRegistrant($params) {
		$contact = parent::mapToRegistrant($params);
		$contact["RegistrantType"]	 = $params["additionalfields"]["Profession"];
		$contact["Details"] 		=  $params["additionalfields"]["Profession"];
		return $contact;
	}
}
?>