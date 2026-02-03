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
require_once(__DIR__ . "/../../../lib/DomainImporter.php");
require_once(__DIR__ . "/../../../lib/ExpiryReportWidget.php");

use ascio\whmcs\ssl\ProductImporter;
use ascio\whmcs\ssl\Installer;
use ascio\whmcs\tools\Settings;
use ascio\whmcs\tools\SettingsTest;
use Illuminate\Database\Capsule\Manager as Capsule;
use ascio\ssl\CertConfig;
use ascio\ssl\CertificateConfig;
use ascio\DomainImporter;
use ascio\ExpiryReportWidget;

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

    /**
     * Expiry Report action (PS-146)
     *
     * Display full domain expiry report with filtering and CSV export
     *
     * @param array $vars Module configuration parameters
     * @return string HTML output
     */
    public function expiryReport($vars)
    {
        $modulelink = $vars['modulelink'];

        // Get filter parameters from request
        $days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
        $tld = isset($_GET['tld']) ? $_GET['tld'] : null;
        $status = isset($_GET['status']) ? $_GET['status'] : null;
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = 25;

        // Handle CSV export
        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            return $this->exportExpiryCsv($days, $tld, $status);
        }

        // Get available TLDs for filter dropdown
        $availableTlds = ExpiryReportWidget::getAvailableTlds();

        // Get expiry stats
        $stats = ExpiryReportWidget::getExpiryStats();

        // Get domains with pagination
        $result = ExpiryReportWidget::getExpiringDomains($days, $tld, $status, $page, $perPage);
        $domains = $result['domains'];
        $totalDomains = $result['total'];
        $totalPages = $result['totalPages'];

        // Build the HTML output
        $html = '<h2><i class="fas fa-calendar-times"></i> Domain Expiry Report</h2>';

        // Stats summary boxes
        $html .= '<div class="row" style="margin-bottom: 20px;">';
        $html .= '<div class="col-sm-3">';
        $html .= '<div class="panel panel-danger"><div class="panel-body text-center">';
        $html .= '<h3 style="margin:0;color:#d9534f;">' . (int)$stats['30'] . '</h3>';
        $html .= '<small>Expiring in 30 days</small>';
        $html .= '</div></div></div>';

        $html .= '<div class="col-sm-3">';
        $html .= '<div class="panel panel-warning"><div class="panel-body text-center">';
        $html .= '<h3 style="margin:0;color:#f0ad4e;">' . (int)$stats['60'] . '</h3>';
        $html .= '<small>Expiring in 60 days</small>';
        $html .= '</div></div></div>';

        $html .= '<div class="col-sm-3">';
        $html .= '<div class="panel panel-success"><div class="panel-body text-center">';
        $html .= '<h3 style="margin:0;color:#5cb85c;">' . (int)$stats['90'] . '</h3>';
        $html .= '<small>Expiring in 90 days</small>';
        $html .= '</div></div></div>';

        $html .= '<div class="col-sm-3">';
        $html .= '<div class="panel panel-info"><div class="panel-body text-center">';
        $html .= '<h3 style="margin:0;color:#5bc0de;">' . (int)$stats['total_active'] . '</h3>';
        $html .= '<small>Total Active Domains</small>';
        $html .= '</div></div></div>';
        $html .= '</div>';

        // Filter form
        $html .= '<div class="panel panel-default">';
        $html .= '<div class="panel-heading"><h3 class="panel-title">Filters</h3></div>';
        $html .= '<div class="panel-body">';
        $html .= '<form method="get" action="" class="form-inline">';
        $html .= '<input type="hidden" name="module" value="asciotools">';
        $html .= '<input type="hidden" name="action" value="expiryReport">';

        // Days filter
        $html .= '<div class="form-group" style="margin-right: 15px;">';
        $html .= '<label for="days">Expiring Within:</label> ';
        $html .= '<select name="days" id="days" class="form-control">';
        foreach ([30, 60, 90, 180, 365] as $d) {
            $selected = ($days == $d) ? ' selected' : '';
            $html .= '<option value="' . $d . '"' . $selected . '>' . $d . ' days</option>';
        }
        $html .= '</select>';
        $html .= '</div>';

        // TLD filter
        $html .= '<div class="form-group" style="margin-right: 15px;">';
        $html .= '<label for="tld">TLD:</label> ';
        $html .= '<select name="tld" id="tld" class="form-control">';
        $html .= '<option value="">All TLDs</option>';
        foreach ($availableTlds as $availableTld) {
            $selected = ($tld === $availableTld) ? ' selected' : '';
            $html .= '<option value="' . htmlspecialchars($availableTld) . '"' . $selected . '>.' . htmlspecialchars($availableTld) . '</option>';
        }
        $html .= '</select>';
        $html .= '</div>';

        // Status filter
        $html .= '<div class="form-group" style="margin-right: 15px;">';
        $html .= '<label for="status">Status:</label> ';
        $html .= '<select name="status" id="status" class="form-control">';
        $html .= '<option value="">All</option>';
        $html .= '<option value="Active"' . ($status === 'Active' ? ' selected' : '') . '>Active</option>';
        $html .= '<option value="Pending"' . ($status === 'Pending' ? ' selected' : '') . '>Pending</option>';
        $html .= '</select>';
        $html .= '</div>';

        $html .= '<button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>';
        $html .= '</form>';
        $html .= '</div></div>';

        // Export button
        $exportUrl = $modulelink . '&action=expiryReport&export=csv&days=' . $days;
        if ($tld) $exportUrl .= '&tld=' . urlencode($tld);
        if ($status) $exportUrl .= '&status=' . urlencode($status);
        $html .= '<div style="margin-bottom: 15px;">';
        $html .= '<a href="' . htmlspecialchars($exportUrl) . '" class="btn btn-success">';
        $html .= '<i class="fas fa-file-csv"></i> Export to CSV</a>';
        $html .= ' <span class="text-muted">(' . $totalDomains . ' domains)</span>';
        $html .= '</div>';

        // Results table
        $html .= '<table class="table table-striped table-hover">';
        $html .= '<thead><tr>';
        $html .= '<th>Domain</th>';
        $html .= '<th>Client</th>';
        $html .= '<th>Expiry Date</th>';
        $html .= '<th>Days Left</th>';
        $html .= '<th>Status</th>';
        $html .= '<th>Actions</th>';
        $html .= '</tr></thead>';
        $html .= '<tbody>';

        if (empty($domains)) {
            $html .= '<tr><td colspan="6" class="text-center text-muted">No domains found matching the criteria.</td></tr>';
        } else {
            foreach ($domains as $domain) {
                $daysClass = 'text-success';
                if ($domain['days_left'] <= 30) {
                    $daysClass = 'text-danger';
                } elseif ($domain['days_left'] <= 60) {
                    $daysClass = 'text-warning';
                }

                $html .= '<tr>';
                $html .= '<td><strong>' . htmlspecialchars($domain['domain']) . '</strong></td>';
                $html .= '<td>';
                $html .= '<a href="clientssummary.php?userid=' . (int)$domain['userid'] . '">';
                $html .= htmlspecialchars($domain['client_name']) . '</a>';
                if ($domain['client_email']) {
                    $html .= '<br><small class="text-muted">' . htmlspecialchars($domain['client_email']) . '</small>';
                }
                $html .= '</td>';
                $html .= '<td>' . htmlspecialchars($domain['expirydate']) . '</td>';
                $html .= '<td class="' . $daysClass . '"><strong>' . (int)$domain['days_left'] . '</strong></td>';
                $html .= '<td><span class="label label-' . ($domain['status'] === 'Active' ? 'success' : 'warning') . '">' . htmlspecialchars($domain['status']) . '</span></td>';
                $html .= '<td>';
                $html .= '<a href="clientsdomains.php?id=' . (int)$domain['id'] . '" class="btn btn-xs btn-default" title="View Domain">';
                $html .= '<i class="fas fa-eye"></i></a> ';
                $html .= '</td>';
                $html .= '</tr>';
            }
        }

        $html .= '</tbody></table>';

        // Pagination
        if ($totalPages > 1) {
            $html .= '<nav aria-label="Page navigation"><ul class="pagination">';

            // Previous button
            if ($page > 1) {
                $prevUrl = $modulelink . '&action=expiryReport&page=' . ($page - 1) . '&days=' . $days;
                if ($tld) $prevUrl .= '&tld=' . urlencode($tld);
                if ($status) $prevUrl .= '&status=' . urlencode($status);
                $html .= '<li><a href="' . htmlspecialchars($prevUrl) . '">&laquo; Previous</a></li>';
            } else {
                $html .= '<li class="disabled"><span>&laquo; Previous</span></li>';
            }

            // Page numbers (show max 10 pages around current)
            $startPage = max(1, $page - 5);
            $endPage = min($totalPages, $page + 5);

            for ($p = $startPage; $p <= $endPage; $p++) {
                $pageUrl = $modulelink . '&action=expiryReport&page=' . $p . '&days=' . $days;
                if ($tld) $pageUrl .= '&tld=' . urlencode($tld);
                if ($status) $pageUrl .= '&status=' . urlencode($status);
                $activeClass = ($p == $page) ? ' class="active"' : '';
                $html .= '<li' . $activeClass . '><a href="' . htmlspecialchars($pageUrl) . '">' . $p . '</a></li>';
            }

            // Next button
            if ($page < $totalPages) {
                $nextUrl = $modulelink . '&action=expiryReport&page=' . ($page + 1) . '&days=' . $days;
                if ($tld) $nextUrl .= '&tld=' . urlencode($tld);
                if ($status) $nextUrl .= '&status=' . urlencode($status);
                $html .= '<li><a href="' . htmlspecialchars($nextUrl) . '">Next &raquo;</a></li>';
            } else {
                $html .= '<li class="disabled"><span>Next &raquo;</span></li>';
            }

            $html .= '</ul></nav>';
        }

        return $html;
    }

    /**
     * Export expiring domains to CSV
     *
     * @param int $days Days filter
     * @param string|null $tld TLD filter
     * @param string|null $status Status filter
     */
    private function exportExpiryCsv($days, $tld, $status)
    {
        // Get all domains (no pagination for export)
        $allDomains = [];
        $page = 1;
        do {
            $result = ExpiryReportWidget::getExpiringDomains($days, $tld, $status, $page, 500);
            $allDomains = array_merge($allDomains, $result['domains']);
            $page++;
        } while ($page <= $result['totalPages']);

        // Generate CSV
        $csv = ExpiryReportWidget::exportToCsv($allDomains);

        // Output CSV with proper headers
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="ascio-expiring-domains-' . date('Y-m-d') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo $csv;
        exit;
    }

    /**
     * Show Domain Import page (PS-147)
     *
     * @param array $vars Module configuration parameters
     * @return string
     */
    public function showDomainImport($vars)
    {
        $modulelink = $vars['modulelink'];

        // Get recent import logs
        $recentLogs = DomainImporter::getRecentLogs(50);
        $logsHtml = '';

        if (!empty($recentLogs)) {
            $logsHtml = '<h3>Recent Import Activity</h3>
            <table class="table table-striped">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Domain</th>
                    <th>Action</th>
                    <th>Client ID</th>
                    <th>Message</th>
                </tr>
            </thead>
            <tbody>';

            foreach ($recentLogs as $log) {
                $log = (object) $log;
                $actionClass = match($log->action) {
                    'imported' => 'success',
                    'skipped' => 'info',
                    'conflict' => 'warning',
                    'unmatched' => 'default',
                    'error' => 'danger',
                    default => 'default'
                };
                $logsHtml .= '<tr>';
                $logsHtml .= '<td>' . htmlspecialchars($log->created_at) . '</td>';
                $logsHtml .= '<td>' . htmlspecialchars($log->domain_name) . '</td>';
                $logsHtml .= '<td><span class="label label-' . $actionClass . '">' . htmlspecialchars($log->action) . '</span></td>';
                $logsHtml .= '<td>' . ($log->client_id ?: '-') . '</td>';
                $logsHtml .= '<td>' . htmlspecialchars($log->message) . '</td>';
                $logsHtml .= '</tr>';
            }

            $logsHtml .= '</tbody></table>';
        }

        return '
<h2>Import Domains from Ascio</h2>

<div class="alert alert-info">
    <strong>How it works:</strong>
    <ul>
        <li>Fetches all domains from your Ascio account</li>
        <li>Matches each domain to a WHMCS client by registrant email or company name</li>
        <li>Unmatched domains are logged but not created (no client auto-creation)</li>
        <li>Existing domains are skipped; conflicts with different clients are flagged</li>
    </ul>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Dry Run (Preview)</h3>
            </div>
            <div class="panel-body">
                <p>Preview what would be imported without making any changes.</p>
                <form method="post" action="' . $modulelink . '&action=runDomainImport">
                    <input type="hidden" name="dry_run" value="1" />
                    <button type="submit" class="btn btn-info">
                        <i class="fa fa-eye"></i> Preview Import
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Run Import</h3>
            </div>
            <div class="panel-body">
                <p>Import all domains from Ascio to WHMCS.</p>
                <form method="post" action="' . $modulelink . '&action=runDomainImport" onsubmit="return confirm(\'Are you sure you want to import domains? This action will create domain records in WHMCS.\');">
                    <input type="hidden" name="dry_run" value="0" />
                    <button type="submit" class="btn btn-success">
                        <i class="fa fa-download"></i> Import Domains
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <form method="post" action="' . $modulelink . '&action=clearImportLogs" style="margin-bottom: 15px;">
            <button type="submit" class="btn btn-default btn-sm" onclick="return confirm(\'Clear all import logs?\');">
                <i class="fa fa-trash"></i> Clear Import Logs
            </button>
        </form>
    </div>
</div>

' . $logsHtml . '
';
    }

    /**
     * Run Domain Import (PS-147)
     *
     * @param array $vars Module configuration parameters
     * @return string
     */
    public function runDomainImport($vars)
    {
        $modulelink = $vars['modulelink'];
        $dryRun = ($_POST['dry_run'] ?? '1') === '1';

        // Get Ascio credentials from settings
        $settings = new Settings("mod_ascio_settings");
        $settingsData = $settings->getAll();

        $environment = $settingsData['Environment'] ?? 'testing';
        $isTestMode = ($environment === 'testing');

        if ($isTestMode) {
            $username = $settingsData['AccountTesting'] ?? '';
            $password = $settingsData['PasswordTesting'] ?? '';
        } else {
            $username = $settingsData['Account'] ?? '';
            $password = $settingsData['Password'] ?? '';
        }

        if (empty($username) || empty($password)) {
            return '
<div class="alert alert-danger">
    <strong>Error:</strong> Ascio API credentials not configured. Please configure them in the Settings section.
</div>
<p><a href="' . $modulelink . '&action=settings" class="btn btn-primary">Go to Settings</a></p>
';
        }

        $params = [
            'Username' => $username,
            'Password' => $password,
            'TestMode' => $isTestMode ? 'on' : '',
        ];

        try {
            $importer = new DomainImporter($params);
            $result = $importer->runImport($dryRun);

            $stats = $result['stats'];
            $results = $result['results'];

            $modeLabel = $dryRun ? 'Preview (Dry Run)' : 'Import Complete';
            $alertClass = $dryRun ? 'info' : 'success';

            $html = '
<h2>Domain Import - ' . $modeLabel . '</h2>

<div class="alert alert-' . $alertClass . '">
    <strong>' . ($dryRun ? 'Preview complete!' : 'Import complete!') . '</strong>
    Processed ' . $result['total_processed'] . ' domains from Ascio.
</div>

<div class="row">
    <div class="col-md-2">
        <div class="panel panel-success">
            <div class="panel-heading">Imported</div>
            <div class="panel-body text-center"><h3>' . $stats['imported'] . '</h3></div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="panel panel-info">
            <div class="panel-heading">Skipped</div>
            <div class="panel-body text-center"><h3>' . $stats['skipped'] . '</h3></div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="panel panel-warning">
            <div class="panel-heading">Conflicts</div>
            <div class="panel-body text-center"><h3>' . $stats['conflicts'] . '</h3></div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="panel panel-default">
            <div class="panel-heading">Unmatched</div>
            <div class="panel-body text-center"><h3>' . $stats['unmatched'] . '</h3></div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="panel panel-danger">
            <div class="panel-heading">Errors</div>
            <div class="panel-body text-center"><h3>' . $stats['errors'] . '</h3></div>
        </div>
    </div>
</div>

<h3>Details</h3>
<table class="table table-striped">
<thead>
    <tr>
        <th>Domain</th>
        <th>Action</th>
        <th>Client ID</th>
        <th>Message</th>
    </tr>
</thead>
<tbody>';

            foreach ($results as $item) {
                $actionClass = match($item['action']) {
                    'imported', 'would_import' => 'success',
                    'skipped' => 'info',
                    'conflict' => 'warning',
                    'unmatched' => 'default',
                    'error' => 'danger',
                    default => 'default'
                };

                $html .= '<tr>';
                $html .= '<td>' . htmlspecialchars($item['domain']) . '</td>';
                $html .= '<td><span class="label label-' . $actionClass . '">' . htmlspecialchars($item['action']) . '</span></td>';
                $html .= '<td>' . ($item['client_id'] ?: '-') . '</td>';
                $html .= '<td>' . htmlspecialchars($item['message']) . '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody></table>

<p>
    <a href="' . $modulelink . '&action=showDomainImport" class="btn btn-info">
        <i class="fa fa-arrow-left"></i> Back to Import
    </a>
</p>
';

            return $html;

        } catch (\Exception $e) {
            return '
<div class="alert alert-danger">
    <strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '
</div>
<p><a href="' . $modulelink . '&action=showDomainImport" class="btn btn-info">Back to Import</a></p>
';
        }
    }

    /**
     * Clear Import Logs (PS-147)
     *
     * @param array $vars Module configuration parameters
     * @return string
     */
    public function clearImportLogs($vars)
    {
        $modulelink = $vars['modulelink'];

        $deleted = DomainImporter::clearLogs();

        return '
<div class="alert alert-success">
    Cleared ' . $deleted . ' import log entries.
</div>
<p><a href="' . $modulelink . '&action=showDomainImport" class="btn btn-info">Back to Import</a></p>
';
    }
}
