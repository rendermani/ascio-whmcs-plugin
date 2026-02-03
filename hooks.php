<?php
/**
 * Ascio WHMCS Registrar Module - Hooks
 *
 * Hooks for domain registration status updates and
 * conditional field JavaScript injection.
 */

use ascio\Request as Request;
use Illuminate\Database\Capsule\Manager as Capsule;

require_once(__DIR__ . "/lib/Request.php");

/**
 * Set domain status after registration/transfer
 */
function hook_ascio_set_domain_status($vars) {
    if (strpos($vars["params"]["registrar"], "ascio") === false) {
        return;
    }

    $request = new Request(array(
        'Account' => $vars["params"]["Username"],
        'Password' => $vars["params"]["Password"]
    ));

    $domain = $vars["params"]["sld"] . "." . $vars["params"]["tld"];
    logActivity("Ascio: Calling hook for domain " . $domain);

    $domainObj = (object) array("DomainName" => $domain);
    $request->setStatus($domainObj, "Pending");
}

add_hook("AfterRegistrarRegistration", 1, "hook_ascio_set_domain_status");
add_hook("AfterRegistrarTransfer", 1, "hook_ascio_set_domain_status");

/**
 * Inject Ascio conditional fields JavaScript in client area
 *
 * This handles dynamic showing/hiding of additional domain fields
 * based on other field values (e.g., Legal Type -> Birth Country for .IT)
 */
function hook_ascio_client_area_head_output($vars) {
    // Only inject on pages that deal with domain registration/configuration
    $relevantPages = array(
        'cart',           // Shopping cart (domain registration)
        'domainregister', // Domain registration
        'domaintransfer', // Domain transfer
        'domainconfigure' // Domain configuration
    );

    $currentPage = isset($vars['filename']) ? basename($vars['filename'], '.php') : '';
    $action = isset($_GET['a']) ? $_GET['a'] : '';

    // Check if we're on a relevant page
    $isRelevant = in_array($currentPage, $relevantPages) ||
                  $action === 'confdomains' ||
                  $action === 'domainaddons' ||
                  strpos($currentPage, 'domain') !== false;

    if (!$isRelevant) {
        return '';
    }

    // Get the path to the JS file relative to WHMCS root
    $jsPath = '../modules/registrars/ascio/assets/js/ascio-fields.js';

    // Add cache buster based on file modification time
    $fullPath = __DIR__ . '/assets/js/ascio-fields.js';
    $cacheBuster = file_exists($fullPath) ? filemtime($fullPath) : time();

    return '<script src="' . $jsPath . '?v=' . $cacheBuster . '"></script>';
}

add_hook("ClientAreaHeadOutput", 1, "hook_ascio_client_area_head_output");

/**
 * Inject Ascio conditional fields JavaScript in admin area
 */
function hook_ascio_admin_area_head_output($vars) {
    // Only inject on domain-related admin pages
    $currentPage = isset($_GET['modop']) ? $_GET['modop'] : '';
    $module = isset($_GET['module']) ? $_GET['module'] : '';

    // Check for domain management pages
    $isRelevant = (strpos($_SERVER['REQUEST_URI'] ?? '', 'clientsdomains') !== false) ||
                  (strpos($_SERVER['REQUEST_URI'] ?? '', 'configdomains') !== false) ||
                  ($currentPage === 'registrar' && $module === 'ascio');

    if (!$isRelevant) {
        return '';
    }

    // Get the path to the JS file relative to admin directory
    $jsPath = '../modules/registrars/ascio/assets/js/ascio-fields.js';

    // Add cache buster
    $fullPath = __DIR__ . '/assets/js/ascio-fields.js';
    $cacheBuster = file_exists($fullPath) ? filemtime($fullPath) : time();

    return '<script src="' . $jsPath . '?v=' . $cacheBuster . '"></script>';
}

add_hook("AdminAreaHeadOutput", 1, "hook_ascio_admin_area_head_output");

/**
 * Additional hook for cart page specifically
 * Ensures JavaScript loads even with AJAX-loaded content
 */
function hook_ascio_shopping_cart_checkout($vars) {
    // This hook fires on the checkout page
    // The JS is already loaded via ClientAreaHeadOutput, but we can add
    // a re-initialization trigger for AJAX-loaded domain fields
    return <<<HTML
<script>
if (typeof AscioFields !== 'undefined') {
    // Re-initialize after a short delay for AJAX content
    setTimeout(function() { AscioFields.init(); }, 500);
}
</script>
HTML;
}

add_hook("ShoppingCartCheckoutOutput", 1, "hook_ascio_shopping_cart_checkout");

/**
 * Display Ascio order status banner in client area domain details
 */
function hook_ascio_client_domain_status($vars) {
    $domainId = $vars['domainid'] ?? null;
    if (!$domainId) return '';

    $ascioStatus = Capsule::table('tbldomains_extra')
        ->where('domain_id', $domainId)
        ->where('name', 'ascio_order_status')
        ->value('value');

    if (!$ascioStatus) return '';

    $statusMap = [
        'Completed' => ['class' => 'success', 'label' => 'Completed'],
        'Failed' => ['class' => 'danger', 'label' => 'Failed'],
        'Invalid' => ['class' => 'danger', 'label' => 'Invalid'],
        'Pending' => ['class' => 'info', 'label' => 'Processing'],
        'Pending_End_User_Action' => ['class' => 'warning', 'label' => 'Action Required'],
        'Pending_Documentation' => ['class' => 'warning', 'label' => 'Documentation Required'],
        'NotReady' => ['class' => 'info', 'label' => 'Not Ready'],
    ];

    $info = $statusMap[$ascioStatus] ?? ['class' => 'info', 'label' => $ascioStatus];

    return '<div class="alert alert-' . $info['class'] . '">'
         . '<strong>Ascio Status:</strong> ' . htmlspecialchars($info['label'])
         . '</div>';
}

add_hook('ClientAreaDomainDetailsOutput', 1, 'hook_ascio_client_domain_status');

/**
 * Display transfer progress tracker and domain history in admin domain details
 * Shows visual progress bar for domains with status "Pending Transfer"
 * Shows domain status history for all Ascio domains (PS-148)
 */
function hook_ascio_admin_client_domains_tab_fields($vars) {
    require_once(__DIR__ . '/lib/TransferTracker.php');
    require_once(__DIR__ . '/lib/DomainHistory.php');

    $domainId = $vars['id'] ?? null;
    if (!$domainId) {
        return [];
    }

    // Get domain status from WHMCS
    $domain = Capsule::table('tbldomains')
        ->where('id', $domainId)
        ->first();

    if (!$domain) {
        return [];
    }

    // Only show for Ascio domains
    if (strpos($domain->registrar ?? '', 'ascio') === false) {
        return [];
    }

    $fields = [];

    // Show transfer progress for Pending Transfer domains
    if ($domain->status === 'Pending Transfer') {
        // Get transfer status
        $status = \ascio\TransferTracker::getTransferStatus($domainId);

        if (!$status) {
            // No tracking data yet - create initial pending status
            \ascio\TransferTracker::updateStatus($domainId, 'pending');
            $status = \ascio\TransferTracker::getTransferStatus($domainId);
        }

        if ($status) {
            // Generate the progress HTML
            $progressHtml = \ascio\TransferTracker::renderProgressHtml($status);
            $fields['Transfer Progress'] = $progressHtml;
        }
    }

    // Show domain history for all Ascio domains (PS-148)
    $history = \ascio\DomainHistory::getHistory($domainId, 20);
    if (!empty($history)) {
        $historyHtml = \ascio\DomainHistory::formatForDisplay($history);
        $fields['Status History'] = $historyHtml;
    }

    return $fields;
}

add_hook('AdminClientDomainsTabFields', 1, 'hook_ascio_admin_client_domains_tab_fields');

/**
 * Include Auto-Expire hooks for threshold-based domain expiration
 * This implements the "AutoExpire OFF" behavior
 */
if (file_exists(__DIR__ . '/hooks/auto_expire_hook.php')) {
    require_once(__DIR__ . '/hooks/auto_expire_hook.php');
}

/**
 * Admin Home Widget: Ascio Domains Expiring Soon (PS-146)
 *
 * Displays a summary of domains expiring in 30/60/90 days on the WHMCS admin dashboard.
 * Clicking the widget links to the full expiry report in Ascio Tools addon.
 */
function hook_ascio_admin_home_widgets($vars) {
    require_once(__DIR__ . '/lib/ExpiryReportWidget.php');

    try {
        $stats = \ascio\ExpiryReportWidget::getExpiryStats();

        // Build widget content
        $widgetContent = '<div class="widget-content-padded">';
        $widgetContent .= '<div class="row">';

        // 30 days column
        $widgetContent .= '<div class="col-sm-4 text-center">';
        $widgetContent .= '<div class="item">';
        $widgetContent .= '<div class="data color-red">' . (int)$stats['30'] . '</div>';
        $widgetContent .= '<div class="note">30 Days</div>';
        $widgetContent .= '</div>';
        $widgetContent .= '</div>';

        // 60 days column
        $widgetContent .= '<div class="col-sm-4 text-center">';
        $widgetContent .= '<div class="item">';
        $widgetContent .= '<div class="data color-orange">' . (int)$stats['60'] . '</div>';
        $widgetContent .= '<div class="note">60 Days</div>';
        $widgetContent .= '</div>';
        $widgetContent .= '</div>';

        // 90 days column
        $widgetContent .= '<div class="col-sm-4 text-center">';
        $widgetContent .= '<div class="item">';
        $widgetContent .= '<div class="data color-green">' . (int)$stats['90'] . '</div>';
        $widgetContent .= '<div class="note">90 Days</div>';
        $widgetContent .= '</div>';
        $widgetContent .= '</div>';

        $widgetContent .= '</div>';

        // Add link to full report
        $widgetContent .= '<div class="text-center" style="margin-top: 10px;">';
        $widgetContent .= '<a href="addonmodules.php?module=asciotools&action=expiryReport" class="btn btn-default btn-sm">';
        $widgetContent .= '<i class="fas fa-list"></i> View Full Report';
        $widgetContent .= '</a>';
        $widgetContent .= '</div>';

        $widgetContent .= '</div>';

        // Add some inline styles for the widget
        $widgetContent .= '<style>
            #AscioExpiringDomains .data { font-size: 28px; font-weight: bold; }
            #AscioExpiringDomains .note { font-size: 12px; color: #888; }
            #AscioExpiringDomains .color-red { color: #d9534f; }
            #AscioExpiringDomains .color-orange { color: #f0ad4e; }
            #AscioExpiringDomains .color-green { color: #5cb85c; }
        </style>';

        return [
            'AscioExpiringDomains' => [
                'title' => 'Ascio Domains Expiring Soon',
                'content' => $widgetContent,
            ],
        ];
    } catch (\Exception $e) {
        // Return error widget if something goes wrong
        return [
            'AscioExpiringDomains' => [
                'title' => 'Ascio Domains Expiring Soon',
                'content' => '<div class="widget-content-padded text-center text-danger">'
                           . '<i class="fas fa-exclamation-triangle"></i> Error loading expiry data'
                           . '</div>',
            ],
        ];
    }
}

add_hook('AdminHomeWidgets', 1, 'hook_ascio_admin_home_widgets');
