<?php
namespace ascio\whmcs\ssl;

use Illuminate\Database\Capsule\Manager as Capsule;
use ascio\v3 as v3;

class SslContacts  {
    protected $clientId; 
    protected $serviceId;
    public $contacts; 
    public $contactsFlat;    
    public  function __construct(Params $params)
    {   
        $this->serviceId = $params->serviceId;
        $this->clientId = $params->userId;
    }
    public function getDropDownOptions($selectedId = NULL) {        
        $postData = array('userid' => $this->clientId );
        $results = localAPI('GetContacts', $postData);
        $options = '<option value="newcontact">New Contact</option><option value="client">Current Client</option>';
        foreach ($results["contacts"]["contact"] as $key => $contact) {
            $company = $contact["companyname"] ? " (".$contact["companyname"].")" : "";
            $name = $contact["firstname"] ." ". $contact["lastname"] .$company;
            $selected = $selectedId == $contact["id"] ? " selected " : ""; 
            $options .= '<option '.$selected.' value="'.$contact["id"].'">'.$name.'</option>';
        }
        return $options;
    }
    protected function readForm($type) {
        global $_POST; 
        $post = $_POST;  
        $data = [];
        $post[$type."Phone"] = $_POST[$type."PhonePrefix"] . "." . $_POST["phonenumber".$type];
        unset($post["phonenumber".$type]);
        unset($post[$type."PhonePrefix"]);
        unset($post["country-calling-code-phonenumber".$type]);
        foreach($post as $key => $value) {            
            if(strpos($key,$type) !== false) {
                $data[$key] = $value;
            }
        }  
        return $data;
    }    
    public function fromForm() {
        $this->contacts = [
            "owner" => $this->readForm("owner"),
            "admin" => $this->readForm("admin"),
            "tech" => $this->readForm("tech")
        ];
        return $this->contacts;
        
    }
    public function toForm() {
        if(!isset($this->contactsFlat)) {
            $data = $this->readDb();
        }  else {
            $data = $this->contactsFlat;
        }    
        $newData = [];
        foreach ($data as $key => $value) {
            $newData[strtolower($key)] = $value;
        }
        return $newData;

    }
    public function writeDb() {
        $this->readDb();
        $data = array_merge($this->contacts["owner"],$this->contacts["admin"],$this->contacts["tech"]);
        $data["user_id"] = $this->clientId; 
        $data["whmcs_service_id"] = $this->serviceId;
     
        if(isset($this->contactsFlat)) {
            $result = Capsule::table('mod_asciossl')
            ->where("whmcs_service_id",$this->serviceId)
            ->update($data);
        } else {
            $result = Capsule::table('mod_asciossl')->insert($data);
        }
    }
    public function readDb() {        
        $user = Capsule::table('mod_asciossl')
        ->select('errors','ownerTitle','ownerFirstName','ownerLastName','ownerCompanyName','ownerEmail','ownerPhone','ownerAddress1','ownerAddress2','ownerCity','ownerState','ownerPostcode','ownerCountry','adminTitle','adminFirstName','adminLastName','adminCompanyName','adminEmail','adminPhone','adminAddress1','adminAddress2','adminCity','adminState','adminPostcode','adminCountry','techTitle','techFirstName','techLastName','techCompanyName','techEmail','techPhone','techAddress1','techAddress2','techCity','techState','techPostcode','techCountry')
        ->where('whmcs_service_id',$this->serviceId)
        ->first();
        if(isset($user)) {
            $this->contactsFlat = $user;
            $user->errors = json_decode($user->errors);
        }

        return $this->contactsFlat;
    }
    public function setRequestFields($contact,$type) {
        $data = $this->contacts[$type];        

        $contact->setFirstName($data[$type."FirstName"]);
        $contact->setLastName($data[$type."LastName"]);
        $contact->setOrgName($data[$type."CompanyName"]);
        $contact->setAddress1($data[$type."Address1"]);
        $contact->setAddress2($data[$type."Address2"]);
        $contact->setCity($data[$type."City"]);
        $contact->setState($data[$type."State"]);
        $contact->setPostalCode($data[$type."Postcode"]);
        $contact->setCountryCode($data[$type."Country"]);
        $contact->setPhone(str_replace(" ","",$data[$type."Phone"]));
        $contact->setEmail($data[$type."Email"]);

        $contactExtensions =  new v3\Extensions(array(
            new v3\KeyValue("Title", $data[$type."Title"])
            )
        );
        $contact->setExtensions($contactExtensions);       
    }
    public function saveToWhmcs() {

    }
    public function getFromApi ($contactId, $lowerCase=true) {
        $fieldList = [
            "address1" => "Address1",
            "address2" => "Address2",
            "city" => "City",
            "companyname" => "CompanyName",
            "country" => "Country",
            "email" => "Email",
            "firstname" => "FirstName",
            "lastname" => "LastName",
            "phone" => "Phone",
            "phonePrefix" => "PhonePrefix",
            "phonenumber" => "PhoneNumber",
            "postcode" => "Postcode",
            "state" => "State"
        ];
        
        if($contactId == "client") {
            $postData = array('clientid' => $this->clientId );
            $client = localAPI('GetClientsDetails', $postData);
            $results = [
                "contacts" => [
                    "contact" => [$client]
                ]
            ];
        } else {
            $postData = array('userid' => $this->clientId );
            $results = localAPI('GetContacts', $postData);
        }

        foreach($results["contacts"]["contact"] as $key => $contact) {
            if($contact["id"] == $contactId || $contactId == "client") {        
                $newContact = [];
                foreach($contact as $key => $value) {
                    if(in_array($key,\array_keys($fieldList))) {
                        if(!$lowerCase) {
                            $key = $fieldList[$key];
                        }
                        $newContact[$key] = $value;
                    }
                }
                $phone = explode(".",$newContact["PhoneNumber"]);
                
                $newContact["Phone"] = $newContact["PhoneNumber"];
                $newContact["phonePrefix"] = $phone[0];
                $newContact["PhoneNumber"] = $phone[1];;
                return $newContact;
            }     
        }
    }
}