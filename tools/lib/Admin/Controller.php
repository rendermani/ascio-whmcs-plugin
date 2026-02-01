<?php
/**
 * Ascio Tools Admin Controller
 *
 * Updated for monorepo structure - SSL module is now at ../ssl/ relative to tools/
 */

namespace WHMCS\Module\Addon\AddonModule\Admin;

// Load from monorepo structure (tools is sibling to ssl)
require_once(__DIR__ . "/../../../ssl/lib/CertificateConfig.php");
require_once(__DIR__ . "/../../ssl/ProductImporter.php");
require_once(__DIR__ . "/../../ssl/Installer/Installer.php");
require_once(__DIR__ . "/../../lib/Settings.php");

use ascio\whmcs\ssl\ProductImporter;
use ascio\whmcs\ssl\Installer;
use ascio\whmcs\tools\Settings;
use ascio\whmcs\tools\SettingsTest;
use Illuminate\Database\Capsule\Manager as Capsule;
use ascio\ssl\CertConfig;
use ascio\ssl\CertificateConfig;

/**
 * Sample Admin Area Controller
 */
class Controller {

    /**
     * Index action.
     *
     * @param array $vars Module configuration parameters
     *
     * @return string
     */
    public function index($vars)
    {
        // Get common module parameters
        $modulelink = $vars['modulelink']; // eg. addonmodules.php?module=addonmodule
        $version = $vars['version']; // eg. 1.0
        $LANG = $vars['_lang']; // an array of the currently loaded language variables
        return '
        <h2>Please select action</h2>
';
    }
    public function install () {
        // In monorepo, SSL module is at ../ssl/ relative to tools/
        // When deployed, it will be at /modules/servers/asciossl/
        $local = __DIR__ . "/../../../ssl";
        $gitBase = "tucowsinc/ascio-whmcs-plugin";
        $installer = new Installer($gitBase, $local, "ssl");
        $html = '<h2>Ascio SSL Installer</h2>';
        $html .= '<h3>Requirements</h3>';
        $html .= $installer->showRequirements();
        return $html;
    }
    public function settings($vars) {
        $modulelink = $vars['modulelink'];
        // Use new unified settings table (with fallback to old)
        $settings = new Settings("mod_ascio_settings");
        return $settings->viewHtml();
    }
    public function showUpload($vars)
    {
        // Get common module parameters
        $modulelink = $vars['modulelink']; // eg. addonmodules.php?module=addonmodule
        $version = $vars['version']; // eg. 1.0
        $LANG = $vars['_lang']; // an array of the currently loaded language variables
        return '
<h2>Import SSL Products</h2>
<p>Please download you pricelist from the portal and include <b>SSL</b> and <b>SSL SAN</b> products. Upload the .csv file here:</p>
<form method="post" action="'.$modulelink.'&action=upload" enctype="multipart/form-data">
        <div class="row">
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="upload" class="control-label">Upload Pricelist</label>
                    <input required="required" type="file" name="prices" id="upload" class="form-control" />
                </div>                    
            </div>             
        </div>
        <div class="row">       
            <div class="col-sm-4">
                <button class="btn btn-success">Preview prices</button>
            </div>
        </div>  
</form>
';
    }    
    public function upload($vars) {
        $pi = new ProductImporter();
        $inputForm = '
            <div class="row">
                <div class="col-sm-2">
                    <div class="form-group">
                        <label for="margin" class="control-label">Add % margin</label>
                        <input required="required" type="text" name="margin" id="margin" class="form-control" />
                    </div>
                </div>
                <div class="col-sm-2">
                    <div class="form-group">
                        <label for="round" class="control-label">Round up to x €</label>
                        <input required="required" type="text" name="round" id="round" class="form-control" />
                    </div>
                </div> 
                <div class="col-sm-2">
                    <div class="form-group">
                        <label for="calculate" class="control-label">Calculate prices</label><br/>
                        <button  role="button" id="calculate" class="btn btn">Calculate</button>
                    </div>
                </div>                    
                <div class="col-sm-2">
                    <div class="form-group">
                        <label for="calculate" class="control-label">Import selected products</label><br/>
                        <button role="button"  id="upload" class="btn btn-success">Upload</button>
                    </div>
                </div>             
            </div>              
        ';
        
        if(isset($_FILES['prices'])){
            $errors= false;
            $file_name = $_FILES['prices']['name'];
            $file_size =$_FILES['prices']['size'];
            $file_tmp =$_FILES['prices']['tmp_name'];
            $file_type=$_FILES['prices']['type'];
            $file_ext=strtolower(end(explode('.',$_FILES['prices']['name'])));
            $extensions= array("csv");
            
            if(in_array($file_ext,$extensions)=== false){
               return "Extension not allowed, please choose a JPEG or PNG file.";
            }            
            $dir = realpath( __DIR__."/../..");
            
            $file = $dir."/import/products.csv";
            if (!is_dir($dir."/import") && !mkdir($dir."/import", 0777, false)) {
                echo ('<div class="alert alert-danger" role="alert">Can\'t create '.$dir.'/import. Please check directory permissions</div>' );
            }

            move_uploaded_file($file_tmp,$file);       
            $pi->readCSV($file);
            echo $inputForm;
            echo '<div id="preview">' . $pi->preview() . '</div>';             
         }
    }
    /**
     * Failed orders action
     */
    
    
    public function displayFailedSslOrders (){
        $data = Capsule::table('mod_asciossl')
        ->select('whmcs_service_id','order_id','common_name','type','code','message','errors')
        ->where("status","Failed")
        ->get();
        $table = '<h2>Failed orders</h2>
        <table class="table">
        <thead>
            <tr>
                <th>View</th>
                <th>WHMCS</th>
                <th>Domain</th>
                <th>Certificate</th>
                <th>Type</th>
                <th>Code</th>
                <th>Message</th>
                <th>Errors</th>
            </tr>
        </thead>
        <tbody>'; 
        $config = new CertificateConfig();
        foreach($data as $key => $row) {
            $table .= '<tr>';
            $cert = $config->get($row->type);
            $row->type =  $cert->name . " (".$cert->type.")"; 
            $row->whmcs_service_id = '<a href="/whmcs/admin/clientsservices.php?id='.$row->whmcs_service_id.'"><span title ="view" class="glyphicon glyphicon-zoom-in"> </span></a>';
            foreach($row as $key => $field) {
                $table .= '<td>'.$field.'</td>';
            }
            $table .= '</tr>';
        }
        $table .= '</tbody></table>';
        echo $table; 
    }

    /**
    * Show action.
     *
     * @param array $vars Module configuration parameters
     *
     * @return string
     */
    public function show($vars)
    {
        // Get common module parameters
        $modulelink = $vars['modulelink']; // eg. addonmodules.php?module=addonmodule
        $version = $vars['version']; // eg. 1.0
        $LANG = $vars['_lang']; // an array of the currently loaded language variables

        // Get module configuration parameters
        $configTextField = $vars['Text Field Name'];
        $configPasswordField = $vars['Password Field Name'];
        $configCheckboxField = $vars['Checkbox Field Name'];
        $configDropdownField = $vars['Dropdown Field Name'];
        $configRadioField = $vars['Radio Field Name'];
        $configTextareaField = $vars['Textarea Field Name'];

        return <<<EOF

<h2>Show</h2>

<p>This is the <em>show</em> action output of the sample addon module.</p>

<p>The currently installed version is: <strong>{$version}</strong></p>

<p>
    <a href="{$modulelink}" class="btn btn-info">
        <i class="fa fa-arrow-left"></i>
        Back to home
    </a>
</p>

EOF;
    }
}
