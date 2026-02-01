<?php
/**
 * Ascio WHMCS Registrar Module - Hooks
 *
 * Hooks for domain registration status updates and
 * conditional field JavaScript injection.
 */

use ascio\v2\domains\Request as Request;

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

    $type = $vars["params"]["regtype"] == "Transfer" ? "Transfer_Domain" : false;
    $domainObj = (object) array("DomainName" => $domain);
    $request->setStatus($domainObj, "Pending", $type);
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
 * Include Auto-Expire hooks for threshold-based domain expiration
 * This implements the "AutoExpire OFF" behavior
 */
if (file_exists(__DIR__ . '/hooks/auto_expire_hook.php')) {
    require_once(__DIR__ . '/hooks/auto_expire_hook.php');
}
