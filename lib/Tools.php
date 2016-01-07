<?php
require_once  dirname(__FILE__)."/../libphonenumber-for-PHP/PhoneNumberUtil.php";

use com\google\i18n\phonenumbers\PhoneNumberUtil;
use com\google\i18n\phonenumbers\PhoneNumberFormat;
use com\google\i18n\phonenumbers\NumberParseException;

class Tools {
	public static function dump($variable, $title, $height="9em") {
		if($title) {
			echo "<h2>".$title."</h2>";
			syslog(LOG_INFO,$title);
		}
		echo "<pre style=\"border: 1px solid #000; height: {$height}; overflow: auto; margin: 0.5em;\">";
		echo nl2br(print_r($variable,1));
		echo "</pre>\n";
	}	
	public static function splitName($name) {
		$spacePos = strpos($name," ");
		$out = array();
		$out["first"] = substr($name,0,$spacePos);
		$out["last"] = substr($name, $spacePos+1);
		return $out;
	}	
	public static function formatError($items,$message) {		
		if(!$items) return "";
		if (!is_array($items)) $items = array($items);
		$message = Tools::cleanString($message);

		$html = "";
		foreach ($items as $nr => $item) {
			$html .=  Tools::cleanString($item->Message)."\n";
		}
		return $html;	
	}
	public static function cleanString($string) {		
		$string = str_replace("$240A", ":", $string);
		$string = str_replace("$240D", ".", $string);
		$string = str_replace("$0A", ":", $string);
		$string = str_replace("$0D", ".", $string);
		return $string;	
	}
	public static function formatOK($message) {
		$html = "<h2>Order completed:".$message.":</h2>";
		return $html;	
	}
	public static function diffContact($newContact,$oldContact) {
		if($newContact->City == NULL) return array();
		$diffs  = array();	
		foreach (get_object_vars($newContact) as $key => $value) {
			$originalValue =  Tools::replaceSpecialCharacters($oldContact->$key);
			if($value != $originalValue ) {
				$diffs[$key] = $value;
				//echo "$key:".$value . " != ". $originalValue  . "<br/>";
			} 		
		}	
		return $diffs;
	}
	public static function log($message) {
		$command 	= "logactivity";
 	 	$adminuser 	= "admin";
 		$values["description"] = $message;
 		$results 	= localAPI($command,$values,$adminuser);
		return $results; 
	}
	public static function compareRegistrant($newContact,$oldContact) {
		$diffs =  Tools::diffContact($newContact,$oldContact);
		if($diffs["Name"] || $diffs["OrgName"] || $diffs["RegistrantNumber"]) return "Owner_Change";
		elseif (count($diffs) > 0) return "Registrant_Details_Update";
		else return false; 
	}
	public static function compareContact($newContact,$oldContact) {
		$diffs =  Tools::diffContact($newContact,$oldContact);
		if (count($diffs) > 0) return "Contact_Update";
		else return false;
	}
	public static function replaceSpecialCharacters($string) {
		$string = str_replace("ü", "u", $string);
		$string = str_replace("ä", "a", $string);
		$string = str_replace("ö", "o", $string);
		$string = str_replace("ß", "s", $string);
		$string = str_replace("Ü", "U", $string);
		$string = str_replace("Ä", "A", $string);
		$string = str_replace("Ö", "O", $string);
		return $string; 
	}
	public static function fixPhone($number,$country) {
		if($number=="") return "";
		$country = strtoupper($country);
		if(preg_match("/^[\+][1-9]{2}\.[0-9]*/",$number)) return $number;
		if((!$number) || (strlen($number)<5)) throw new AscioException("Phone number too short: ".$number);
		if(!(substr($number,0,1) == "+" || substr($number,0,1) == "0")) throw new AscioException("Phone numbers should start with 0 or +: ".$number);
		if(!preg_match("/^[0\+]/",$number)) $number = '+' . $number;
		$phoneUtil = PhoneNumberUtil::getInstance();
		try {	
			$numberProto = $phoneUtil->parseAndKeepRawInput($number, $country);	

			if(!$phoneUtil->isValidNumber($numberProto)) return $number;
			$newNumber = $phoneUtil->formatOutOfCountryCallingNumber($numberProto, PhoneNumberFormat::E164);	
			$newNumber = preg_replace("/( )(.*)/", ".$2", $newNumber);
			$newNumber = preg_replace("/[ \/]/", "", $newNumber);
			return $newNumber;
		} catch (Exception $e) {
			throw new AscioException("Error converting phone number: ".$number.". ".$e);
		}
	}	
	public static function cleanAscioParams($ascioParams) {
		foreach ($ascioParams as $key => $value) {
			if(is_array($value)) {
				$ascioParams[$key] =  Tools::cleanAscioParams($value);			
			} elseif (strlen($value) > 0) {
				$ascioParams[$key] =$value;	
			}
		}
		return $ascioParams; 
	}
	public static function generateEppCode($nrOfCharacters, $specialCharaters) {
		$code = "";
		$length = strlen($specialCharaters) -1;
		for ($i = 0; $i < $nrOfCharacters; $i++) {
			$code .= substr($specialCharaters,rand(0,$length),1); 
		}
		return $code; 
	}
	public static function createEmailTemplates() {
		$usedTemplates = ["EPP Code","Ascio Status"];
		$templates = array(
			"EPP Code" => "INSERT INTO `whmcs`.`tblemailtemplates` (`id`, `type`, `name`, `subject`, `message`, `attachments`, `fromname`, `fromemail`, `disabled`, `custom`, `language`, `copyto`, `plaintext`, `created_at`, `updated_at`) VALUES (NULL, 'domain', 'EPP Code', 'New EPP Code for \{\$domain_name\}', '<p>Dear {$client_name},</p> <p>A new EPP Code was generated for the domain {$domain_name}: {$code}</p> <p>You may transfer away your domain with the new EPP-Code.</p> <p>{$signature}</p>', '', '', '', '0', '1', '', '', '0', CURDATE(), CURDATE());",
			"Ascio Status" => "INSERT INTO `whmcs`.`tblemailtemplates` (`id`, `type`, `name`, `subject`, `message`, `attachments`, `fromname`, `fromemail`, `disabled`, `custom`, `language`, `copyto`, `plaintext`, `created_at`, `updated_at`) VALUES (NULL, 'domain', 'Ascio Status', '{$orderType} {$domain_name}: {$status}', '<p>Dear {$client_name},</p> <p>we received following status for your domain {$domain_name} ({$orderType}): {$status}</p> <p>{$errors}</p> <p> </p> <p>{$signature}</p>', '', '', '', '0', '1', '', '', '0', CURDATE(), CURDATE());"
		);
		$found = 0;
		$command = "getemailtemplates";
 		$adminuser = "admin";
 		$values["type"] = "domain"; 
		$results = localAPI($command,$values,$adminuser);
 		foreach($results["emailtemplates"]["emailtemplate"] as $key => $value) {
 			$existingTemplates[$value["name"]] = true;
 		}
 		foreach($usedTemplates as $key => $name) {
 			if(!$existingTemplates[$name]) {
 				mysql_query($templates[$name]);
 				if(mysql_error()) {
 					echo "error ". mysql_error()."\n";
 				}				
 			}
 		}
	}
	public static function addNote($domainName,$message) {
		$adminuser = "admin";
		$values["domain"] = $domainName;	
		$command = "getclientsdomains";
		$results = localAPI($command, $values, $adminuser);
		$domains = $results["domains"]["domain"];
		$domain  = $domains[count($domains)-1];
		$adminuser = "admin";

		$command = "updateclientdomain";
		$values["domainId"] = $domain["id"];
		$values["notes"] = $domain["notes"]."\n[".date("Y-m-d H:i:s")."] ". $message;
		return  localAPI($command, $values, $adminuser);
	}
	public static function getDomainId($domain) {
		$query = 'SELECT id FROM  `tbldomains` WHERE domain =  "'.$domain.'" LIMIT 0 , 1';
		$result = mysql_query($query);
		$row = mysql_fetch_assoc($result);		
	    $id = $row["id"];
	    return $id; 
	}	
	public static function setExpireDate($domain) {
		$tmpDate = explode("T", $domain->ExpDate);
		$expirydate = str_replace("-", "", $tmpDate[0]);
		$command 	= "updateclientdomain";
		$adminuser 	= "admin";
		$values["domain"] = $domain->DomainName;
		$values["expirydate"] = $expirydate;
 		$results 	= localAPI($command,$values,$adminuser);
 		return $results;
	}
}
class AscioException extends Exception { }
?>