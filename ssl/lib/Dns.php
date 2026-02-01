<?php
namespace ascio\whmcs\ssl;

require_once("Error.php");
if(!class_exists("DnsService")) {
    require_once(__DIR__."/_DnsService.php");
}
class Dns {
    protected $account;   
    protected $zoneName;
    protected $fullDomain;
    protected $dnsName;
    protected $dnsValue;
    /**
     * @var Fqdn $fqdn Full qualified domain name
     */
    protected $fqdn;
    public function __construct (Params $params,Fqdn $fqdn) {
        if(isset($params->account) || isset($params->testAccount)) {
            $credentials = $params->getCredentials(true);
            $this->account = $credentials["Account"];
            $this->client = new \DnsService($credentials["Account"],$credentials["Password"],""); 
        }       
        $this->zoneName = $fqdn->getDomain();
        $this->fullDomain = $fqdn->getFqdn(); 
        $this->fqdn = $fqdn;       
    }    
    public function addVerificationRecord($dnsName,$dnsValue)  {  
        $this->dnsName = $dnsName; 
        $this->dnsValue = $dnsValue;       
        
        if( $this->dnsName=="DNS TXT Record") {
            $record =  $this->createTXT();
        } else {
            $record = $this->createCNAME();
        }
        if($this->hasZone()) {
            $this->addRecord($record);
        } else {
            $this->createZone($record);
        }
    } 
    protected function createCNAME () {
        $record =  new \CNAME();
        $record->Source = $this->dnsName;
        $record->Target = $this->dnsValue;
        $record->TTL = 3600;
        return $record; 
    
    }
    protected function createTXT () {
        $record =  new \TXT();
        $record->Source = $this->fqdn->getSslAuth();
        $record->Target = $this->dnsValue;
        $record->TTL = 3600;
        return $record; 
    }   
    protected function addRecord ($record) {
        try {
            $createRecord = new \CreateRecord();
            $createRecord->zoneName = $this->zoneName; 
            $createRecord->record = $record;  
            $response = $this->client->CreateRecord($createRecord);
            } catch (\Exception $e) {
                echo $this->client->__getLastRequest();
               throw  new AscioSystemException($e->faultstring, $e->faultcode);            
            }
        $result = $response->CreateRecordResult;
        if($result->StatusCode !== 200) {
            throw  new AscioUserException($result->StatusMessage,$result->StatusCode);
        }  
        return $result;
    
    }
    protected function updateTxtRecord($record) {
        try {
            $updateRecord = new \UpdateRecord();
            $updateRecord->zoneName = $this->zoneName; 
            $updateRecord->record = $record;  
            $response = $this->client->UpdateRecord($updateRecord);
            } catch (\Exception $e) {
                throw new AscioSystemException($e->faultstring, $e->faultcode);            
            }
        $result = $response->UpdateRecordResult;
        if($result->StatusCode !== 200) {
            throw  new AscioUserException($result->StatusMessage,$result->StatusCode);
        } 
        return $result;
    }
    protected function hasZone () {
        try {            
            
            $getZone = new \GetZone();
            $getZone->zoneName = $this->zoneName;
            $response = $this->client->GetZone($getZone); 
            if($response->GetZoneResult->StatusCode ==200) {
                $this->zone = $response->zone;
                return true;
            } else return false; 
            
        } catch (\Exception $e) {
               throw new AscioSystemException($e->faultstring, $e->faultcode);
        }
    }    
    protected function createZone ($records) {
        try {
            $createZone = new \CreateZone(); 
            $createZone->zoneName = $this->zoneName;
            $createZone->owner =$this->account;            
            $response = $this->client->CreateZone($createZone);
            $this->addRecord($records);
           } catch (\Exception $e) {
               var_dump($e);
               echo $this->client->__getLastRequest();
               throw new AscioSystemException($e->faultstring, $e->faultcode);           
           }
       $result = $response->CreateZoneResult;
        if($result->StatusCode !== 200) {
            throw new AscioUserException($result->StatusMessage,$result->StatusCode);
        }
       return $result;
    }   
    public function digNs() {
        $results = dns_get_record($this->fqdn->getDomain(),"NS");
        $out = [];
        foreach($results as $key => $result) {
            if(strpos($result["hostname"],$contains) !== false) {
                $out[] = $result;
            }
        }
        return $out;        
    }
    public function digVerification($source,$target) {
        $results = dns_get_record($this->fqdn->getDomain(),CNAME_TXT);
        if($source == "DNS TXT Record") {            
            foreach(dns_get_record($this->fqdn->getSslAuth(),DNS_TXT) as $key => $record) {
                if($record["txt"]==$target) {
                    return true;
                }
            }
            foreach(dns_get_record($this->fqdn->getSslAuth(),DNS_TXT) as $key => $record) {
                if($record["txt"]==$target) {
                    return true;
                }
            }  
        } else {
            foreach (dns_get_record($source,DNS_CNAME) as $key => $record) {
                if( $record["target"]==$target) {
                    return true;
                }
            } ;
            
        }    
    }
}