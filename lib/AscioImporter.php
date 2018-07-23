<?php

error_reporting(E_ALL);
ini_set('display_errors', "on");

require_once(realpath(dirname(__FILE__))."/../../../../init.php");
require_once(realpath(dirname(__FILE__))."/../../../../includes/registrarfunctions.php");
require_once(realpath(dirname(__FILE__))."/Request.php");


class AscioImport {
    function __construct($fileName)
    {
        $this->fileName = $fileName;
        $cfg = getRegistrarConfigOptions("ascio");
        $cfg["Username"] = "yyy";
        $cfg["Password"] = "xxx";
        $this->request = new Request($cfg);
    }
    function start() {
        $this->readCsv();
    }
    function readCsv () {
        if (($handle = fopen("../import/".$this->fileName, "r")) !== FALSE) {
            fgetcsv($handle, 3000, ",");
            while (($data = fgetcsv($handle, 3000, ";")) !== FALSE) {
                $this->createOrder($data);
            }
            fclose($handle);
        }
    }  
    function createOrder ($row) {
        $domainName = $row[1]; 
        $domainHandle = $row[0];     
        $domain = $this->request->getDomain($domainHandle);
        if($domain->Status == "DELETED") return;
        $params = array(
            'clientid' => '1',
            'domain' => array($domainName),
            'domaintype' => array('register'),
            'regperiod' => array($domain->RegPeriod),
            'paymentmethod' => 'banktransfer',
            'noinvoice' => TRUE,
            'noinvoiceemail' => TRUE,
            'noemail' => TRUE,

        );
        $params = $this->addNameservers($domain,$params);
        $results = localAPI('AddOrder', $params);
        $domainId = $results["domainids"];
        $this->request->params["domainid"] = $domainId;        
        $domain->domainId = $domainId;
        $this->request->setDomainStatus($domain);			
        DomainCache::put($domain);
        $this->request->setHandle($domain);
        echo "imported ".$domainName;     
    }
    function addNameservers($domain,$params) {
        for($z=1; $z < 6; $z++ ) {
            $nsName = "NameServer".$z;
            $nsNameWhmcs = "nameserver".$z;
            if(isset($domain->NameServers->$nsName)) {
                $params[$nsNameWhmcs]=$domain->NameServers->$nsName->HostName;
            }
        }
        return $params;
    }
}
$importer = new AscioImport("import.csv");
$importer->start();

?>