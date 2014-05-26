<?php
//todo make it perfect
//need to extend additionaldomainfields.php

class fr extends Request {	
	protected function mapToRegistrant($params) {
		$contact = parent::mapToRegistrant($params);
		$contact["RegistrantType"] 		= "company";
		$contact["RegistrantNumber"] 	= "123456789";
		return $contact;
	}

}
?>