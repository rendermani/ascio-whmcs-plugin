<?php
require(__DIR__."/lib/Settings.php");
use ascio\whmcs\tools\Settings;
use ascio\whmcs\tools\SettingsTest;

header('Content-Type: application/json');
class AccountValidation {
    /**
     * @var Settings $settings
     */
    protected $settings;
    /**
     * @var SettingsTest $settingsTest
     */
    protected $settingsTest;
    /**
     * @var bool $testMode
     */
    private $testMode; 
    private $env;

    public function __construct()
    {
        global $_GET;
        $this->settings = new Settings("mod_asciossl_settings");
        $this->settings->readDb();

        $this->settingsTest = new SettingsTest($this->settings);
        $this->testMode = $_GET["environment"] == "testing";
        $this->env =  $_GET["environment"];
    }
    public function testDomainAccount () {
        $error = false; 
        $result = "";
        try {      
            $sessionId = $this->settingsTest->login($this->testMode);
            $this->settingsTest->availability($this->testMode,$sessionId);
            $result .=  "Availability Check ".$this->env." OK<br/>";
            $this->settingsTest->logout($this->testMode,$sessionId);
        } catch (\Exception $e) {
            $result .=  "Error: ".$e->getCode()." - ".$e->getMessage()."<br/>";
            $error = true; 
        }
        return json_encode(["message" => $result, "error" => $error]);        
    }
    public function testDnsAccount () {
        $error = false; 
        $result = ""; 
        try {
            $this->settingsTest->dns();
            $result .=  "DNS Live OK<br/>";
        } catch (\Exception $e) {
            $result .=  "Error: ".$e->getCode()." - ".$e->getMessage()."<br/>";
            $error = true; 
        }
        return json_encode(["message" => $result, "error" => $error]);        
    }
}

if($_SESSION["adminid"] < 1) {
    echo "Invalid Session";
    die();
}

$result = "";
$accountValidation = new AccountValidation();
switch($_GET["type"]) {
    case "domain" : echo $accountValidation->testDomainAccount(); break;
    case "dns"    : echo $accountValidation->testDnsAccount(); break;
    default       : echo "please specify a type";
}
