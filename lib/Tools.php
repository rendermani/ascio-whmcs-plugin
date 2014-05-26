<?
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
		$html = "<h2>Following errors occurred in: ".$message."</h2><ul>";
		if (!is_array($items)) $items = array($items);
		foreach ($items as $nr => $item) {
			$html .= "<li style='list-style-type: disc; color: red;'>".$item->Message."</li>";
		}
		$html .= "</ul><p>Please change your settings and resubmit the order.</p>";
		return $html;	
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
		if((!$number) || (strlen($number)<5)) return;
		if(!$country) $country = "DE";
		$phoneUtil = PhoneNumberUtil::getInstance();
		try {
			$numberProto = $phoneUtil->parseAndKeepRawInput($number, $country);
		} catch (NumberParseException $e) {
			echo $e;
		}
		if(!$phoneUtil->isValidNumber($numberProto)) return $number;
		$newNumber = $phoneUtil->formatOutOfCountryCallingNumber($numberProto, "US");
		//echo $newNumber;
		return str_replace(" ",".",$newNumber) ;
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
}

?>
