<?php
/**
 * Ascio Tools - WHMCS Addon Module
 *
 * Admin addon for managing Ascio products:
 * - SSL product/pricing import from CSV
 * - Settings management for all Ascio modules
 * - Failed orders viewer
 * - Module installation status
 *
 * Installation: Copy to /modules/addons/asciotools/
 * Activate in WHMCS Admin → Setup → Addon Modules
 *
 * @copyright Copyright (c) Tucows Inc.
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

require_once(__DIR__ . "/lib/Admin/AdminDispatcher.php");
require_once(__DIR__ . "/lib/Admin/Controller.php");

use WHMCS\Module\Addon\AddonModule\Admin\AdminDispatcher;
use WHMCS\Module\Addon\AddonModule\Client\ClientDispatcher;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Module configuration.
 *
 * @return array
 */
function asciotools_config()
{
    return [
        'name' => 'Ascio Tools',
        'description' => 'Admin tools for Ascio modules: SSL product import, settings management, and module installation.',
        'author' => 'Tucows Inc.',
        'language' => 'english',
        'version' => '2.0',
        'fields' => [],
    ];
}

/**
 * Activate - Called upon module activation.
 *
 * Creates the shared settings table used by all Ascio modules.
 *
 * @return array
 */
function asciotools_activate()
{
    try {
        // Create shared settings table (used by all Ascio modules)
        if (!Capsule::schema()->hasTable('mod_ascio_settings')) {
            Capsule::schema()->create('mod_ascio_settings', function ($table) {
                $table->increments('id');
                $table->string('name', 255)->unique();
                $table->text('value')->nullable();
                $table->enum('role', ['User', 'Admin', ''])->default('User');
                $table->string('description', 500)->nullable();
                $table->index('name', 'idx_name');
                $table->index('role', 'idx_role');
            });

            // Insert default settings
            Capsule::table('mod_ascio_settings')->insert([
                ['name' => 'Account', 'value' => '', 'role' => 'User', 'description' => 'Live API account username'],
                ['name' => 'Password', 'value' => '', 'role' => 'User', 'description' => 'Live API account password'],
                ['name' => 'AccountTesting', 'value' => '', 'role' => 'User', 'description' => 'Test/Demo API account username'],
                ['name' => 'PasswordTesting', 'value' => '', 'role' => 'User', 'description' => 'Test/Demo API account password'],
                ['name' => 'Environment', 'value' => 'testing', 'role' => 'User', 'description' => 'Environment: testing or live'],
                ['name' => 'DbVersion', 'value' => '2.0', 'role' => 'Admin', 'description' => 'Database schema version'],
            ]);
        }

        // Migrate from old SSL settings table if exists
        if (Capsule::schema()->hasTable('mod_asciossl_settings')) {
            $sslSettings = Capsule::table('mod_asciossl_settings')->get();
            foreach ($sslSettings as $setting) {
                Capsule::table('mod_ascio_settings')
                    ->updateOrInsert(
                        ['name' => $setting->name],
                        ['value' => $setting->value, 'role' => $setting->role]
                    );
            }
        }

        return [
            'status' => 'success',
            'description' => 'Ascio Tools activated. Shared settings table created. Configure credentials in the addon settings.',
        ];

    } catch (\Exception $e) {
        return [
            'status' => 'error',
            'description' => 'Activation failed: ' . $e->getMessage(),
        ];
    }
}

/**
 * Deactivate - Called upon module deactivation.
 *
 * Note: Does NOT drop the settings table as other modules depend on it.
 *
 * @return array
 */
function asciotools_deactivate()
{
    // Don't drop mod_ascio_settings - other modules use it
    return [
        'status' => 'success',
        'description' => 'Ascio Tools deactivated. Settings table preserved for other Ascio modules.',
    ];
}

/**
 * Upgrade - Called after module update.
 *
 * @param array $vars Including 'version' key with previously installed version
 * @return void
 */
function asciotools_upgrade($vars)
{
    $previousVersion = $vars['version'];

    // Upgrade from 1.x to 2.0
    if (version_compare($previousVersion, '2.0', '<')) {
        // Ensure settings table exists and has all columns
        if (Capsule::schema()->hasTable('mod_ascio_settings')) {
            if (!Capsule::schema()->hasColumn('mod_ascio_settings', 'description')) {
                Capsule::schema()->table('mod_ascio_settings', function ($table) {
                    $table->string('description', 500)->nullable()->after('role');
                });
            }
        }
    }
}

/**
 * Admin Area Output.
 *
 * @param array $vars Module configuration parameters
 * @return array
 */
function asciotools_output($vars)
{
    $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

    $dispatcher = new AdminDispatcher();

    $response = $dispatcher->dispatch($action, $vars);

    echo $response;
}

/**
 * Admin Area Sidebar Output.
 *
 * @param array $vars Module configuration parameters
 * @return string
 */
function asciotools_sidebar($vars)
{
    $modulelink = $vars['modulelink'];

    $sidebar = '<span class="header"><img src="images/icons/domainreg.png" class="absmiddle" width="16" height="16" /> Ascio Tools</span>';
    $sidebar .= '<ul class="menu">';
    $sidebar .= '<li><a href="' . $modulelink . '">Dashboard</a></li>';
    $sidebar .= '<li><a href="' . $modulelink . '&action=settings">Settings</a></li>';
    $sidebar .= '<li><a href="' . $modulelink . '&action=showUpload">Import SSL Products</a></li>';
    $sidebar .= '<li><a href="' . $modulelink . '&action=displayFailedSslOrders">Failed SSL Orders</a></li>';
    $sidebar .= '<li><a href="' . $modulelink . '&action=install">Module Status</a></li>';
    $sidebar .= '</ul>';

    return $sidebar;
}

/**
 * Client Area Output.
 *
 * @param array $vars Module configuration parameters
 * @return array
 */
function asciotools_clientarea($vars)
{
    return [
        'pagetitle' => 'Ascio Tools',
        'breadcrumb' => ['index.php?m=asciotools' => 'Ascio Tools'],
        'templatefile' => 'overview',
        'vars' => [],
    ];
}
