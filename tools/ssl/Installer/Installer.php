<?php
namespace ascio\whmcs\ssl;
require_once(__DIR__ . "/../../lib/Versions.php");
require_once(__DIR__ . "/../../lib/Error.php");

use ascio\whmcs\ssl\AscioSystemException;
use ascio\whmcs\tools\DbVersions;
use ascio\whmcs\tools\FsVersions;
use Illuminate\Database\Capsule\Manager as Capsule;

class Installer {
    /**
     * @var FsVersions $fsVersions
     */
    public $fsVersions;
    /**
     * @var DbVersions $dbVersions
     */
    public $dbVersions;
    protected $reqiurements;
    protected $gitBase;
    protected $gitClone;
    protected $module;
    protected $localPath;
    public function __construct($gitBase, $localPath, $module)
    {
        $this->gitBase = "https://raw.githubusercontent.com/" . $gitBase . "/master";
        $this->gitClone = "https://github.com/" . $gitBase . "/archive/master.zip";
        $this->localPath = realpath($localPath);
        $this->module = $module;
        // For monorepo, module.json is in ssl/ subdirectory
        $gitUrl = $this->gitBase . "/ssl/module.json";
        $this->fsVersions = new FsVersions("ssl", $gitUrl, $localPath);
        $this->dbVersions = new DbVersions("ssl", $gitUrl, $localPath);
        // Use new unified settings table (with fallback check)
        $this->dbVersions->getDb("mod_ascio_settings", "mod_asciossl");
    }
    public function showRequirements () {
        $r= new Requirements($this->module,$this->gitBase,$this->localPath);        
        $update = $r->add(!$this->fsVersions->needsUpdate(),"Needs module update ".$this->fsVersions->getStatus());
        $update->setAction("fs","Update all Files");
        $update = $r->add(!$this->dbVersions->needsUpdate(),"Needs database update ".$this->dbVersions->getStatus());
        $update->setAction("db","Update DB");
        $soap = $r->add(class_exists("SoapClient"),"PHP-SOAP installed");
        $soap->setInstructions("Please install PHP-SOAP on your server.");
        $soap->setSystemRequirement();

        $this->reqiurements = $r;
        return $r->getHtml();
    }
    public function  doDatabaseUpdates () {
        if(!$this->dbVersions->needsUpdate()) {
            return;
        }
        if($this->dbVersions->getLocalVersion() == 0) {
            $this->createDatabase();
        } else  {
            $this->updateDatabase();
        }
    }
    public function doFsUpdates() {  
        if($this->fsVersions->isUpToDate()) {
            return;
        }
        $this->backupFs();      
        $downloadZip = file_get_contents($this->gitClone);
        $zipLocation = "/tmp/ascio-".$this->module.".zip";
        file_put_contents($zipLocation,$downloadZip);
        
        $zip = new Zipper;
        $res = $zip->open($zipLocation);
        if ($res === TRUE) {
            $dir = $this->localPath; 
            // delete old 
            if(file_exists($dir)) {
                $di = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS);
                $ri = new \RecursiveIteratorIterator($di, \RecursiveIteratorIterator::CHILD_FIRST);
                foreach ( $ri as $file ) {
                    $file->isDir() ?  rmdir($file) : unlink($file);
               }
            } else {
                if (!mkdir($dir)) {
                    throw new AscioException("Can't create ".$dir,401);
                }
            }
           // extract file 
           $zip->extractSubdirTo($this->localPath,"ascio-ssl-whmcs-plugin-master");                                 
            $zip->close();
        } else {
            throw new AscioException("Wrong permissions while extracting zip",401);
        }
    }
    private function backupFs() {
        $date = $heute = date("Y-m-d-H-i-s"); 
        $dir = realpath(__DIR__."/../backup");
        $zipFile =$dir."/backup-".$date.".zip";        
        $zip = new Zipper();        
        $zip->open($zipFile,\ZipArchive::CREATE);
        $zip->addDir($this->localPath);
        $zip->close();
    }
    protected function createDatabase() {
        $url = $this->gitBase."/install/install.sql";
        $sql = file_get_contents($url);
        if($sql) {
            $this->executeDbTransaction($sql);
        }
    }
    protected function updateDatabase() {
        foreach($this->dbVersions->getUpdates() as $key => $update) {
            $url = $this->gitBase."/install/".$update.".sql";
            $sql = file_get_contents($url);
            if($sql) {
                $this->executeDbTransaction($sql);
            }
        }
    }
    protected function executeDbTransaction($sql) {
        $sql = str_replace("START TRANSACTION;","",$sql);
        $sql = str_replace("COMMIT;","",$sql);
        $sql = str_replace("SET AUTOCOMMIT = 0;","",$sql);
        $pdo = Capsule::connection()->getPdo();
        $pdo->beginTransaction();                
        try {
            foreach(explode(";",$sql) as $key => $statement) {
                $statement = $pdo->prepare($statement);  
                $statement->execute();                
            }                                  
            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw new AscioSystemException($e->getMessage()."\n".$sql);            
        }
    }
}

Class Requirements {
    public $requirements = []; 
    public $module;
    public $localPath;
    public $git; 
    public function __construct($module,$git,$localPath) {
        $this->module = $module;
        $this->git = $git; 
        $this->localPath = $localPath;
    }
    public function add($valid, $text) : Requirement {
        $req = new Requirement($valid,$text);
        $req->setGit($this->git);
        $req->setLocalPath($this->localPath);
        $req->setModule($this->module);
        $this->requirements[] = $req; 
        return $req;
    }
    public function isValid () : bool {
        foreach($this->requirements as $key => $requirement) {
            if(!$requirement->isValid()) return false; 
        }
        return true; 
    }
    public function isInvalid () : bool {
        return !$this->isValid();
    }
    public function getHtml() {
        $html = "";
        foreach($this->requirements as $key => $requirement) {
            $html .= $requirement->getHtml(); 
        }
        if($this->isSystemOk()) {
            
            if($this->isOk()) {
                $html .= "<br/><p>Everything is up-to-date.</p>" ;
            } else {
                $html .= '<br/><div class="form-group"><button role="button" type="button" class="btn btn-success" id="update">Update</button></div>';
            }
            
        } else {
            $html .= "<br/><p>Please fix requirements before continuing.</p>" ;
        }
        
        return $html; 
    }
    public function isSystemOk() {
        foreach($this->requirements as $key => $requirement) {
            if(!$requirement->isValid() && $requirement->isSystemRequirement()) return false; 
        }
        return true; 
    }
    public function isOk() {
        foreach($this->requirements as $key => $requirement) {
            if(!$requirement->isValid()) return false; 
        }
        return true; 
    }
}
class Requirement {
    private $valid; 
    private $text;
    private $instructions = false;
    private $action = false;
    private $actionButton = false;
    private $systemRequirement=false; 
    private $git;
    private $localPath;
    private $module;
    public function __construct($valid,$text) 
    {   
        $this->text = $text; 
        $this->valid = $valid;
    }
    public function isValid () : bool {
        return $this->valid;
    }
    public function isInvalid (): bool {
        return !$this->valid;
    }
    public function isSystemRequirement() {
        return $this->systemRequirement;
    }
    public function setGit($git) {
        $this->git=$git;
    }
    public function setLocalPath($localPath) {
        $this->localPath = $localPath;
    }
    public function setModule($module) {
        $this->module = $module;
    }
    public function setSystemRequirement() {
        $this->systemRequirement=true;
    }    
    public function getHtml () {
        if($this->isValid()) {
            $icon =  "ok";
            $color = "darkgreen";
        } else {
             $icon = "remove";
             $color = "darkred";
        }
        return  '
            <div class="row installer-task" >
                <div class="col-sm-1" style="width:20px;color:'.$color.'"><span id="icon-'.$this->action.'" class="glyphicon glyphicon-'.$icon.'"> </span></div>
                <div class="ol-sm-11 col-md-5 col-lg-4" style="color:'.$color.'" id="text-'.$this->action.'" >'.$this->text.'</div>
                '.$this->getAction().$this->getInstructions().'
            </div>
            ';    
    }
    public function setAction($action,$text) {
        $this->action = $action;
        $this->actionButton  = $text; 
    }
    public function setInstructions($text) {
        $this->instructions  = $text; 
    }
    private function getAction () {
        if($this->action && $this->isInvalid()) {
            return '
                <div 
                    class="col-sm-5 update-action" 
                    data-action="'.$this->action.'" 
                    data-module="'.$this->module.'"
                    data-git="'.$this->git.'"
                    data-local-path="'.$this->localPath.'"
                    ></div>';
        }
        return ""; 
    }
    private function getInstructions () {
        if($this->instructions && $this->isInvalid()) {
           return '<div class="col-sm-5">'.$this->instructions.'</div>';
        }
        return "";
    }
}
class Zipper extends \ZipArchive {     
    public function addDir($path) { 
        $this->addEmptyDir($this->getRelPath($path)); 
        $nodes = glob($path . '/*'); 
        foreach ($nodes as $node) { 
            print $node . "\n"; 
            if (is_dir($node)) { 
                $this->addDir($node); 
            } else if (is_file($node))  { 
                $this->addFile($node, $this->getRelPath($node)); 
            } 
        } 
    }
    protected function getRelPath($path) {
        return  "/modules/".explode("/modules/",$path)[1];
    } 
    public function extractSubdirTo($destination, $subdir)
    {
      $errors = array();

      // Prepare dirs
      $destination = str_replace(array("/", "\\"), DIRECTORY_SEPARATOR, $destination);
      $subdir = str_replace(array("/", "\\"), "/", $subdir);

      if (substr($destination, mb_strlen(DIRECTORY_SEPARATOR, "UTF-8") * -1) != DIRECTORY_SEPARATOR)
        $destination .= DIRECTORY_SEPARATOR;

      if (substr($subdir, -1) != "/")
        $subdir .= "/";

      // Extract files
      for ($i = 0; $i < $this->numFiles; $i++)
      {
        $filename = $this->getNameIndex($i);

        if (substr($filename, 0, mb_strlen($subdir, "UTF-8")) == $subdir)
        {
          $relativePath = substr($filename, mb_strlen($subdir, "UTF-8"));
          $relativePath = str_replace(array("/", "\\"), DIRECTORY_SEPARATOR, $relativePath);

          if (mb_strlen($relativePath, "UTF-8") > 0)
          {
            if (substr($filename, -1) == "/")  // Directory
            {
              // New dir
              if (!is_dir($destination . $relativePath))
                if (!@mkdir($destination . $relativePath, 0755, true))
                  $errors[$i] = $filename;
            }
            else
            {
              if (dirname($relativePath) != ".")
              {
                if (!is_dir($destination . dirname($relativePath)))
                {
                  // New dir (for file)
                  @mkdir($destination . dirname($relativePath), 0755, true);
                }
              }

              // New file
              if (@file_put_contents($destination . $relativePath, $this->getFromIndex($i)) === false)
                $errors[$i] = $filename;
            }
          }
        }
      }

      return $errors;
    }        
} // class Zipper 
    