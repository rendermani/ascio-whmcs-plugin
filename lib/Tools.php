<?php
use Illuminate\Database\Capsule\Manager as Capsule;
require_once  __DIR__ . '/../vendor/autoload.php';

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
 	 	$adminuser 	= Tools::getApiUser();
 		$values["description"] = $message;
 		$results 	= localAPI($command,$values,$adminuser);
		return $results; 
	}
	public static function logModule($action, $requestData, $responseData) {
		if(isset($requestData["order"])) {
			$orderType = " [".$requestData["order"]["Type"] ."]"; 
		} else $orderType ="";
		$password = isset($requestData["session"]) ? $requestData["session"]["Password"] : null;
		logModuleCall(
			'ascio',
			$action . $orderType,
			$requestData ,			
			$responseData,
			json_encode($responseData), 
			array(
				$requestData["sessionId"],
				$password
				)
			);
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
		$phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
		try {	
			$numberProto = $phoneUtil->parseAndKeepRawInput($number, $country);	
			if(!$phoneUtil->isValidNumber($numberProto)) return $number;
			return "+".$numberProto->getCountryCode().".". $numberProto->getNationalNumber();
		} catch (Exception $e) {
			throw new AscioException("Error converting phone number: ".$number.". ".$e->getMessage());
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
		$usedTemplates = array('EPP Code','Ascio Status');
		$templates = array(
			"EPP Code" => "INSERT INTO `tblemailtemplates` (`id`, `type`, `name`, `subject`, `message`, `attachments`, `fromname`, `fromemail`, `disabled`, `custom`, `language`, `copyto`, `plaintext`) VALUES (NULL, 'domain', 'EPP Code', 'New EPP Code for \{\$domain_name\}', '<p>Dear {\$client_name},</p> <p>A new EPP Code was generated for the domain {\$domain_name}: {\$code}</p> <p>You may transfer away your domain with the new EPP-Code.</p> <p>{\$signature}</p>', '', '', '', '0', '1', '', '', '0');",
			"Ascio Status" => "INSERT INTO `tblemailtemplates` (`id`, `type`, `name`, `subject`, `message`, `attachments`, `fromname`, `fromemail`, `disabled`, `custom`, `language`, `copyto`, `plaintext`) VALUES (NULL, 'domain', 'Ascio Status', '{\$orderType} {\$domain_name}: {\$status}', '<p>Dear {\$client_name},</p> <p>we received following status for your domain {\$domain_name} (\{\$orderType}): {\$status}</p> <p>{\$errors}</p> <p> </p> <p>{\$signature}</p>', '', '', '', '0', '1', '', '', '0');"
		);
		$found = 0;
		$command = "getemailtemplates";
 		$adminuser = Tools::getApiUser();
 		$values["type"] = "domain"; 
		$results = localAPI($command,$values,$adminuser);
 		foreach($results["emailtemplates"]["emailtemplate"] as $key => $value) {
 			$existingTemplates[$value["name"]] = true;
 		}
 		foreach($usedTemplates as $key => $name) {
 			if(!$existingTemplates[$name]) {
 				mysql_query($templates[$name]);
 				if(mysql_error()) {
 					echo "Error writing email-templates (".$name."): ". mysql_error()."\n";
 				}				
 			}
 		}
	}
	public static function addNote($domainName,$message) {
		$adminuser = Tools::getApiUser();
		$values["domain"] = $domainName;	
		$command = "getclientsdomains";
		$results = localAPI($command, $values, $adminuser);
		$domains = $results["domains"]["domain"];
		$domain  = $domains[count($domains)-1];
		$adminuser = Tools::getApiUser();

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
	public static function getDomainIdFromOrder($order) {
		$comment = json_decode($order->TransactionComment);		
		if($comment == NULL && is_object($comment)) {
			$domainId   = Tools::getDomainId($domainName);
		} else {
			$domainId   = $comment->domainId;
		}
		$order->Domain->domainId = $domainId; 
		return $domainId;
	}	
	public static function setExpireDate($domain) {
		$tmpDate = explode("T", $domain->ExpDate);
		$expirydate = str_replace("-", "", $tmpDate[0]);
		$command 	= "updateclientdomain";
		$adminuser 	= Tools::getApiUser();
		$values["domain"] = $domain->DomainName;
		$values["expirydate"] = $expirydate;
 		$results 	= localAPI($command,$values,$adminuser);
 		return $results;
	}
	public static function getApiUser() {
		global $cachedAdminUser; 
		if($cachedAdminUser) return $cachedAdminUser;
		$result = Capsule::select("select username,notes from tbladmins");
		foreach ($result as $key => $user) {
			if($user->notes=="apiuser") return $user->username;
			$admin = $user->username;
		}	
		$cachedAdminUser = $admin; 
		return $admin;
	}
	public static function reformatPrices($result) {
		$prices= $result->PriceInfo->Prices;
		$pricesTmp = array();
		foreach($prices->Price as $key => $price) {					
			$type = $price->OrderType;
			if( !isset($pricesTmp[$type]) || $pricesTmp[$type]->Period > $price->Period ) {
				$pricesTmp[$type] = $price;
			}
		}
		return array(
			'register' => $pricesTmp['Register_Domain']->Price,
			'renew' => $pricesTmp['Renew_Domain']->Price,
			'CurrencyCode' => $result->PriceInfo->Currency,
		);
	}
}
class AscioException extends Exception { }
?>