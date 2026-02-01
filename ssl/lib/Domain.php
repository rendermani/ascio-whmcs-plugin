<?php
namespace ascio\whmcs\ssl;
require_once("Error.php");

class Domain {
    public $name;
    protected $sessionId; 
    protected $client;
    protected $testmode;
    public function __construct($name) {
        $this->name = $name; 
    }
    public function login(Params $params) {
        $this->testmode = $params->testmode;
        $this->client = new \SoapClient($params->getWsdlV2(),array( "trace" => 1 ));
        try{	
            $result = $this->client->logIn($params->getCredentials());
            if($result->LogInResult->Values) {
                echo "Login ResultCode : ".$result->LogInResult->ResultCode."\r\n";
                echo "Login ResultMessage : ".$result->LogInResult->Message."\r\n";
                foreach($result->LogInResult->Values as $key => $value) {
                    echo $value->string."\r\n";
                }
            } else {
                $this->sessionId = $result->sessionId; 
            }
        } catch(Exception $e) {
            throw  new AscioSystemException($e->getMessage(),$e->getCode());
        }
        
    }
    public function search () {                   
        $clauses= [[
            "Attribute" => "DomainName",
            "Operator" => "Is",
            "Value" => $this->name
        ]];
        $criteria= array(
            "Clauses" => $clauses,
            "Mode" => "Strict",
            "Withoutstates" => [],
            "Withstates" => "Active"
        );
        $searchDomain= array(
            "sessionId" => $this->sessionId,
            "criteria" => $criteria
        );
        try{	
            $result = $this->client->searchDomain($searchDomain);
            if($result->SearchDomainResult->Values) {
                foreach($result->SearchDomainResult->Values as $key => $value) {
                    echo $value->string."\r\n";
                }
            } else {
                return $result->domains->Domain;
            }
        } catch(Exception $e) {
            throw  new AscioSystemException($e->getMessage(),$e->getCode());
        }
    }
    public function hasAscioDns() {
        //TODO: doesn't work with live domains
        return true;
        return ["success","true"];
        $domain = $this->search();
        if(!$domain) {
            $result = [
                "error" => "Domain ".$this->name."not found in your account",
                "template_code" => "no_ns_domain"

            ];
            if($this->testmode) {
                return ["success","true"];
            } else {
                throw new AscioUserException($result["error"],404,"no_ns_domain",$result);
            }
            
        }
        // get registrar settings 
        // get ascio regex
        // also check that 
        $nameservers = [];
        foreach($domain->NameServers->NameServer as $key => $nameserver) {
            if(strpos($nameserver->HostName,"ascio") !== false)  {
                return ["success","true"];
            }
            $nameservers[] = $nameserver->HostName;
        }
        $result = [
            "error" => "Please use configure these nameservers",
            "nameservers" => $nameservers,
            "code" => "wrong_ns"
        ];
        throw new AscioUserException($result["error"],404,null,$result) ;


    }
}

