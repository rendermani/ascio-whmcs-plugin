<?php
namespace ascio\whmcs\tools;
require_once("Error.php");
require_once(__DIR__."/../../../../init.php");
if(!class_exists("DnsService")) {
    require_once(__DIR__."/DnsService.php");
}
use ascio\whmcs\ssl as ssl;
use Illuminate\Database\Capsule\Manager as Capsule;
use ascio\whmcs\ssl\AscioUserException;

class Settings {
    protected $settings;
    public $Account;
    public $Password;
    public $AccountTesting;
    public $PasswordTesting;
    public $Environment;
    public $CreateDns;
    public $RequireDomain;
    private $table; 
    private $writeResult;
    private $postVars;
    public function __construct($table)
    {
        global $_POST;
        $this->postVars = count($_POST) > 0  ? $_POST : false; 
        $this->table = $table; 
        if($this->postVars) {
            unset($this->postVars["token"]);
            $this->readForm();           
            $this->writeDb();
        } else {
            $this->readDb();
        }
    }
    public function readDb() {
        $settingsResult = Capsule::table($this->table)
        ->where("role","=", "User")
        ->get();
         foreach($settingsResult as $key =>  $setting) {
            $name = $setting->name;
            //TODO: Decode value
            $this->$name = $setting->value;
        }  
    }
    public function readForm() {
        if($this->postVars) {
            foreach($this->postVars as $key => $value) {
                $this->$key = $value;
            }
        }
    }
    public function validate() {

    }
    public function writeDb() {
        $this->CreateDns = $this->postVars["CreateDns"] == 1 ? 1 : 0;
        $this->RequireDomain = $this->postVars["RequireDomain"] == 1 ? 1 : 0;
        $this->postVars["RequireDomain"]  = $this->RequireDomain;
        //TODO: Encode value
        foreach($this->postVars as $key => $value) {            
            if(isset($value)) {
                Capsule::table($this->table)
                ->where(["name"=> $key])
                ->update(["value"=>$value]);
            }
        }
        $this->writeResult = "Settings saved.";

    }
    public function viewHtml() {
        global $_POST,$_SESSION;
        $liveAccountActive = $this->Environment == 'testing' ? '' : 'checked="checked"';
        $testAccountActive = $this->Environment == 'testing' ? 'checked="checked"' : '' ;
        $dnsActive         = $this->CreateDns == 1 ? 'checked="checked"' : '';
        $requireDomain     = $this->RequireDomain == 1 ? 'checked="checked"' : '';        
        $html ='
            <h2>SSL Settings</h2>
            <form class="formgroup" id="settingsform" action="?module=asciotools&action=settings" method="post">
                <div class="row">
                    <div class="col-sm-4">
                    
                        <div class="formgroup">
                            <label for="Account">Live-Account <span id="progress-live-domain-account"> </span></label>
                            <input type="text" name="Account" class="form-control" id="Account" value="'.$this->Account.'"/>
                        </div>
                        <div class="formgroup">
                            <label for="Password">Live-Password <span id="progress-live-domain-password"> </span></label>
                            <input type="password" name="Password" class="form-control" id="Password "value="'.$this->Password.'"/>
                        </div>
                        <div class="formgroup">
                            <label class="radio-inline"><input type="radio" name="Environment"  id="UseLiveAccount"  value="live"'.$liveAccountActive.'>Use live account</label>                             
                            <label class="radio-inline"><input type="radio" name="Environment"  id="UseTestAccount"  value="testing"'.$testAccountActive.'>Use test account</label>                             
                        </div> 
  
                    </div>
                    <div class="col-sm-4">
                        <div class="formgroup">
                            <label for="AccountTesting">Test-Account <span id="progress-testing-domain-account"> </span></label>
                            <input type="text" name="AccountTesting" class="form-control" id="AccountTesting" value="'.$this->AccountTesting.'"/>
                        </div>
                        <div class="formgroup">
                            <label for="PasswordTesting">Test-Password <span id="progress-testing-domain-password"> </span></label>
                            <input type="password" name="PasswordTesting" class="form-control" id="PasswordTesting" value="'.$this->PasswordTesting.'"/>
                        </div>
                    </div>                
                </div>  
                <div class="row">
                    <div class="col-sm-12">
                        <div class="formgroup">
                            <label class="checkbox-inline"><input type="checkbox" name="CreateDns" id="CreateDns" value="1"  '.$dnsActive.'/>Activate Auto Create DNS Zones/Records  <span id="progress-live-dns"></label>
                        </div> 
                        <div class="formgroup">
                            <label class="checkbox-inline"><input type="checkbox" name="RequireDomain" id="RequireDomain" value="1"  '.$requireDomain.'/>Auto Create DNS needs an existing Domain in WHMCS</label>
                        </div> 
                        <div class="formgroup">                       
                            <br/>                       
                            <button class="btn" type="button" id="validate">Validate</button> <button class="btn btn-success" id="save" role="button">Save settings</button> 
                        </div> 
                    
                    </div>
                </div>  
            </form>
            <div id="result">'.$this->writeResult.'</div>
        
        ';    
        return $html;    
    }
    public function test($env) {

    }

}
class SettingsTest {
    /**
     * @var Settings $settings
     */
    protected $settings; 
    protected $sessionId;
    public function __construct(Settings $settings)
    {
        $this->settings = $settings;   
    }
    public function login($testMode) {
       
        if($testMode) {
            $session= array(
                "Account" => $this->settings->AccountTesting,
                "Password" => $this->settings->PasswordTesting
            );
        } else {
            {
                $session= array(
                    "Account" => $this->settings->Account,
                    "Password" => $this->settings->Password
                );
            }
        }     
        //LogIn

        $logIn= array(
            "session" => $session
        );
        $client = $this->getSoapClient($testMode);
        $result = $client->logIn($logIn);
        
        if($result->LogInResult->ResultCode == 401) {
            throw new ssl\AscioUserException("Login: ".$result->LogInResult->Message,$result->LogInResult->ResultCode);
        } else {
            $this->sessionId = $result->sessionId;
        } 
        return $this->sessionId;        
    }
    public function availability($testMode,$sessionId) {
        $client = $this->getSoapClient($testMode);
        $availabilityCheck= array(
            "sessionId" => $sessionId,
            "domains" => ["test"],
            "tlds" => ["com"],
            "quality" => "Smart"
        );
        $result = $client->availabilityCheck($availabilityCheck);
        if($result->AvailabilityCheckResult->ResultCode == 401) {
            throw new ssl\AscioUserException("Availability Check: ". $result->AvailabilityCheckResult->Message. " .Please contact your Account-Manager",401); 
        }
    }
    public function logout($testMode,$sessionId) {
        $client = $this->getSoapClient($testMode);
        $result = $client->logOut(["sessionId" => $sessionId]);
        return $result;
    }
    public function hasCredentials($testMode) {
        if($testMode && $this->settings->AccountTesting && $this->settings->PasswordTesting) {
            return true;             
        }
        if($testMode==false && $this->settings->Account && $this->settings->Password) {
            return true;             
        }
        return false; 
    }
    public function dns () {
        if(!$this->hasCredentials(false)) {
            throw new ssl\AscioUserException("DNS needs live credentials",401);
        }          
        $client = new \DnsService($this->settings->Account,$this->settings->Password,""); 
        $getZone = new \GetZone();
        $getZone->zoneName = "teskkkkt.de";
        $response = $client->GetZone($getZone); 
        $debug = json_encode($response->GetZoneResult);
        if($response->GetZoneResult->StatusCode==403) {
            $message = "DNS Check: ". $response->GetZoneResult->StatusMessage. " .The AscioDNS Password must match the Account-Password. Please contact your Account-Manager for further advice.";
            throw new ssl\AscioException($message,401); 
        } else return true;             
    }
    private function getSoapClient($testMode) {
        $wsdl = $testMode ? "https://aws.demo.ascio.com/2012/01/01/AscioService.wsdl" :"https://aws.ascio.com/2012/01/01/AscioService.wsdl";
       return new \SoapClient($wsdl,array( "trace" => 1 ));
    }
}


/*


*/