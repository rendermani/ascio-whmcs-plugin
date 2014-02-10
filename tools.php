<?php 

Class Tools {
  public static function splitName($name) {
    $spacePos = strpos($name," ");
    $out = array();
    $out["first"] = substr($name,0,$spacePos);
    $out["last"] = substr($name, $spacePos+1);
    return $out;
  }

  // map contact from Ascio to WHMCS - admincompanyname
  public static function mapToContact($params, $type) {
    $contactName = array();
    $prefix = "";
    if($type == "Registrant") {
      $contactName["Name"] = $params["firstname"] . " " . $params["lastname"];
      //$contactName["NexusCategory"] = $params["Nexus Category"];
      //$contactName["RegistrantNumber"] = "55203780600585";
    } else {
      $prefix = strtolower($type);
      $contactName["FirstName"] = $params[$prefix . "firstname"];
      $contactName["LastName"] = $params[$prefix . "lastname"];
    }
    $contact = Array(
      'OrgName'     =>  $params[$prefix . "companyname"],
      'Address1'    =>  $params[$prefix . "address1"],  
      'Address2'    =>  $params[$prefix . "address2"],
      'PostalCode'  =>  $params[$prefix . "postcode"],
      'City'      =>  $params[$prefix . "city"],
      'State'     =>  $params[$prefix . "state"],   
      'CountryCode'   =>  $params[$prefix . "country"],
      'Email'     =>  $params[$prefix . "email"],
      'Phone'     =>  $params[$prefix . "phonenumber"],
      'Fax'       =>  $params[$prefix . "faxnumber"]);
        
    return array_merge($contactName,$contact);
  }

  // WHMCS has 2 contact structures. Flat and nested.
  // This function in converting from adminfirstname to Admin["First Name"]
  public static function mapContactToAscio($params,$type) {

    $ascio = (object) array(
      'OrgName'         => $params["Organisation Name"],
      'Address1'        => $params["Address 1"],
      'Address2'        => $params["Address 2"],
      'PostalCode'        => $params["ZIP Code"],
      'City'          => $params["City"],
      'State'           => $params["State"],
      'CountryCode'       => $params["Country"],
      'Email'         => $params["Email"],
      'Phone'         => $params["Phone"],
      'Fax'           => $params["Fax"]
    );
    if($type=="Registrant") {
      $ascio->Name = $params["First Name"]. " ". $params["Last Name"];    
    } else {
      $ascio->FirstName = $params["First Name"];
      $ascio->LastName = $params["Last Name"];
    }
    return $ascio; 

  }

  public static function mapToNameservers($params) {
    return array (
      'NameServer1' => Array('HostName' => $params["ns1"]), 
      'NameServer2' => Array('HostName' => $params["ns2"]),
      'NameServer3' => Array('HostName' => $params["ns3"]),
      'NameServer4' => Array('HostName' => $params["ns4"])
    );
  }

  public static function cleanAscioParams($ascioParams) {
    foreach ($ascioParams as $key => $value) {
      if(is_array($value)) {
        $ascioParams[$key] = Tools::cleanAscioParams($value);      
      } elseif (strlen($value) > 0) {
        $ascioParams[$key] = $value;  
      }
    }
    return $ascioParams;
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

  public static function mapResult($status) {
    $resultMap = array (
      "Completed" => "Active",
      "Failed"  => "Cancelled",
      "Invalid" => "Cancelled",
      "Documentation_Not_Approved" => "Cancelled",
      "Pending_Documentation" => "Pending",
      "Pending_End_User_Action" => "Pending",
      "Pending_Post_Processing" => "Pending",
      "Pending_NIC_Processing" => "Pending"
    );
    return $resultMap[$status];
  }

  public static function diffContact($newContact,$oldContact) {
    if($newContact->City == NULL) return array();
    $diffs  = array();  
    foreach (get_object_vars($newContact) as $key => $value) {
      $originalValue = Tools::replaceSpecialCharacters($oldContact->$key);
      if($value != $originalValue ) {
        $diffs[$key] = $value;
        //echo "$key:".$value . " != ". $originalValue  . "<br/>";
      }     
    } 
    return $diffs;
  }

  public static function compareRegistrant($newContact,$oldContact) {
    $diffs = Tools::diffContact($newContact,$oldContact);
    if($diffs["Name"] || $diffs["OrgName"] || $diffs["RegistrantNumber"]) return "Owner_Change";
    elseif (count($diffs) > 0) return "Registrant_Details_Update";
    else return false; 
  }

  public static function compareContact($newContact,$oldContact) {
    $diffs = Tools::diffContact($newContact,$oldContact);
    if (count($diffs) > 0) return "Contact_Update";
    else return false;
  }

  public static function htmldump($variable, $height="9em") {
    echo "<pre style=\"border: 1px solid #000; height: {$height}; overflow: auto; margin: 0.5em;\">";
    var_dump($variable);
    echo "</pre>\n";
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

  public static function mapToOrder($params,$orderType) {
    $domainName = $params["sld"] ."." . $params["tld"];
    syslog(LOG_INFO,  $orderType . ": ".$domainName);
    $registrant = Tools::mapToContact($params,"Registrant");
    $order = array( 
      'Type' => $orderType, 
      'Domain' => array( 
        'DomainName' => $domainName,
        'RegPeriod' =>  $params["regperiod"],
        'AuthInfo'  =>  $params["eppcode"],
        'DomainPurpose' =>  $params["Application Purpose"],
        'Registrant'  => Tools::mapToContact($params,"Registrant"),
        'AdminContact'  => Tools::mapToContact($params,"Admin"), 
        'TechContact'   => Tools::mapToContact($params,"Admin"), 
        'BillingContact'=> Tools::mapToContact($params,"Admin"),
        'NameServers'   => Tools::mapToNameservers($params),
        'Comment'   => $params["userid"]
      ),
      'Comments'  =>  $params["userid"]
    );
    //echo(nl2br(print_r($order,1)));
    return array(
      'sessionId' => "set-it-later",
      'order' => $order
    );
  }
}

?>