<?php
namespace ascio\whmcs\tools;
require_once("Error.php");

use ascio\whmcs\ssl\AscioSystemException;
use Illuminate\Database\Capsule\Manager as Capsule;

class Versions {
    protected $moduleConfig;
    protected $remoteModuleConfig;
    protected $versions; 
    protected $localVersion;
    protected $remoteVersion;
    protected $remoteVersions;
    protected $storageType ="fs";
    protected $gitUrl;
    protected $localPath;     
    function __construct($moduleId,$gitUrl,$localPath)
    {
        $storageType = $this->storageType;  
        $this->localPath = $localPath;
        $file = $localPath."/module.json";        
        $cfg = file_get_contents($file);
        if($cfg) {
            $this->moduleConfig = json_decode($cfg);
            $this->localVersion = reset($this->moduleConfig->$storageType->versions)->version;        
        } else {
            $this->localVersion = 0;
        }
        $cfg = file_get_contents($gitUrl);   
        if(!$cfg) throw new AscioSystemException("URL not found ".$gitUrl);                   
        $this->remoteModuleConfig = json_decode($cfg);
        
               
        $versions = $this->remoteModuleConfig->$storageType->versions;
        
        $this->remoteVersion = reset($versions)->version;
        $this->remoteVersions = $versions;
        foreach($versions as $key => $version) {
            $this->versions["v".$version->version] = new Version($this->getLocalVersion(),$version->version);            
        }
    }
    public function setGit($gitUrl) {
        $this->getUrl($gitUrl);
    }
    public function getLocalVersion () {
        return $this->localVersion ? $this->localVersion : 0 ;
    }
    public function needsUpdate() : bool {
        if($this->localVersion < $this->remoteVersion) return true;
        return false; 
    }
    public function isUpToDate() : bool {
        return !$this->needsUpdate();
    }
    public function getStatus() {
        return "Local: ".$this->getLocalVersion().", Remote: ".$this->remoteVersion;
    }
    public function getUpdates() {
        $updates = [];
        $needsUpdate = false; 
        foreach(array_reverse($this->versions) as $key => $version) {
            /**
             * @var Version $version
             */
            if($needsUpdate) {
                $updates[] = $version->remote;             
            }
            if($version->needsUpdate()) {
                $needsUpdate = true;
            } 
        }
        return $updates;
    }
}
class DbVersions extends Versions {
    protected $storageType ="db";
    private $dbReadComplete = false;

    public function __construct($moduleId,$gitUrl,$localPath) {
        parent::__construct($moduleId,$gitUrl,$localPath);
    }
    public function getDb ($settingsTable,$defaultTable) {
        if($this->dbReadComplete) return $this->localVersion;
        if(!isset($settingsTable)) throw new AscioSystemException("No Settings-Table provided");
        $existingTable = Capsule::table("INFORMATION_SCHEMA.TABLES")
        ->where(["TABLE_NAME"=>$settingsTable])
        ->first();
        if(!$existingTable) {
            $this->localVersion = 0; 
            return 0;
        }
        $v =  Capsule::table($settingsTable)
        ->where(["name"=>"DbVersion"])
        ->first();
        if($v) {
            $this->localVersion =  $v->value;            
        } else {
            $this->localVersion = 0;            
        }
        if($this->localVersion == 0) {
            $v =  Capsule::table("INFORMATION_SCHEMA.TABLES")
            ->where(["TABLE_NAME"=>$defaultTable])
            ->first();
            if($v) $this->localVersion = 0.1;
        }
        $this->dbReadComplete = true;
        return $this->localVersion;
    }

}
class FsVersions extends Versions {
    protected $storageType ="fs";
}

class Version {
    public $local;
    public $remote;
    public function __construct($local,$remote)
    {   
        $this->local = $local;
        $this->remote = $remote;        
    }
    public function needsUpdate() {
        if(!$this->local) return true;
        return !($this->local <= $this->remote);
    }
    public function getStatus() {
        return "Local: ".$this->local.", Remote: ".$this->remote;
    }
}
