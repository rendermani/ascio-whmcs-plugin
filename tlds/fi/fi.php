<?php

namespace ascio\v2\domains;

class fi extends Request {	
	protected function mapToRegistrant($params) {
		$contact = parent::mapToRegistrant($params);
		$map = array(
			"Individual" 					=> "0",
			"Company" 						=> "1",
			"Association" 					=> "2",
			"Foundation/Institution" 		=> "3",
			"Political party" 				=> "4",
			"Municipality/Township" 		=> "5",
			"State/Government" 				=> "6",
			"Public corporation/community" 	=> "7"
		);
		$contact["RegistrantNumber"] 	= $params["additionalfields"]["Identification Number"];
		$contact["RegistrantType"] 		= $map[$params["additionalfields"]["Legal Type"]];
		return $contact;
	}

}
?>