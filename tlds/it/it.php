<?php

class it extends Request {	
	protected function mapToRegistrant($params) {
		$contact = parent::mapToRegistrant($params);
		$map = array(
			"Italian and foreign natural persons" 	=> "1",
			"Companies/one man companies" 			=> "2",
			"Freelance workers/professionals" 		=> "3",
			"non-profit organizations" 				=> "5",
			"public organizations" 					=> "4",
			"other subjects" 						=> "6",
			"non natural foreigners" 				=> "7"
		);
		$contact["RegistrantType"] 		= $map[$params["additionalfields"]["Legal Type"]];
		$contact["RegistrantNumber"]  	= $params["additionalfields"]["Tax ID"];
		return $contact;
	}
}
?>