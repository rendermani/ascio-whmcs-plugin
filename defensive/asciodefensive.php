<?php

/**
 * Ascio Defensive Registration (DPML) WHMCS Module
 *
 * Provides defensive domain registration services via the Ascio API.
 * Protects trademarks by blocking similar domain registrations.
 *
 * @copyright Copyright (c) Tucows Inc.
 */

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

// Load dependencies
require_once __DIR__ . '/../core/lib/autoload.php';
require_once __DIR__ . '/lib/Defensive.php';
require_once __DIR__ . '/lib/DefensiveCallback.php';

use Ascio\Core\Params;
use Ascio\Defensive\Defensive;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Module metadata.
 *
 * @return array
 */
function asciodefensive_MetaData(): array
{
    return [
        'DisplayName' => 'Ascio Defensive Registration (DPML)',
        'APIVersion' => '1.1',
        'RequiresServer' => false,
    ];
}

/**
 * Product configuration options.
 *
 * @return array
 */
function asciodefensive_ConfigOptions(): array
{
    return [
        'MarkHandle' => [
            'Type' => 'text',
            'Size' => 50,
            'Default' => '',
            'Description' => 'TMCH Mark Handle (optional)',
        ],
    ];
}

/**
 * Provision a new defensive registration.
 *
 * @param array $params WHMCS parameters
 * @return string "success" or error message
 */
function asciodefensive_CreateAccount(array $params): string
{
    try {
        // Ensure table exists
        if (!asciodefensive_EnsureTable()) {
            return 'Database table not found. Please run module installation.';
        }

        $coreParams = new Params($params);
        $defensive = new Defensive($coreParams);

        // Get form data
        $formData = [
            'name' => $params['domain'] ?? $params['customfields']['DefensiveDomain'] ?? '',
            'mark_handle' => $params['configoption1'] ?? $params['customfields']['MarkHandle'] ?? '',
            'period' => asciodefensive_GetPeriod($params),
        ];

        // Owner contact from client details
        $contactData = [
            'owner_name' => $params['clientsdetails']['firstname'] . ' ' . $params['clientsdetails']['lastname'],
            'owner_email' => $params['clientsdetails']['email'],
            'owner_company' => $params['clientsdetails']['companyname'] ?? '',
            'owner_address1' => $params['clientsdetails']['address1'] ?? '',
            'owner_address2' => $params['clientsdetails']['address2'] ?? '',
            'owner_city' => $params['clientsdetails']['city'] ?? '',
            'owner_state' => $params['clientsdetails']['state'] ?? '',
            'owner_postcode' => $params['clientsdetails']['postcode'] ?? '',
            'owner_country' => $params['clientsdetails']['countrycode'] ?? '',
            'owner_phone' => $params['clientsdetails']['phonenumber'] ?? '',
        ];

        // Initialize record
        $defensive->fromForm(array_merge($formData, $contactData));
        $defensive->writeDb();

        // Submit order
        $result = $defensive->register($contactData);

        if ($result['success']) {
            return 'success';
        }

        return $result['error'] ?? $result['message'] ?? 'Unknown error';

    } catch (\Exception $e) {
        logModuleCall('asciodefensive', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        return $e->getMessage();
    }
}

/**
 * Terminate defensive registration.
 *
 * @param array $params WHMCS parameters
 * @return string "success" or error message
 */
function asciodefensive_TerminateAccount(array $params): string
{
    try {
        $coreParams = new Params($params);
        $defensive = new Defensive($coreParams);

        $result = $defensive->terminate();

        if ($result['success']) {
            return 'success';
        }

        return $result['error'] ?? $result['message'] ?? 'Unknown error';

    } catch (\Exception $e) {
        logModuleCall('asciodefensive', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        return $e->getMessage();
    }
}

/**
 * Renew defensive registration.
 *
 * @param array $params WHMCS parameters
 * @return string "success" or error message
 */
function asciodefensive_RenewAccount(array $params): string
{
    try {
        $coreParams = new Params($params);
        $defensive = new Defensive($coreParams);

        $data = $defensive->readDb();
        $contactData = [
            'owner_name' => $data->owner_name ?? '',
            'owner_email' => $data->owner_email ?? '',
            'owner_company' => $data->owner_company ?? '',
            'owner_address1' => $data->owner_address1 ?? '',
            'owner_address2' => $data->owner_address2 ?? '',
            'owner_city' => $data->owner_city ?? '',
            'owner_state' => $data->owner_state ?? '',
            'owner_postcode' => $data->owner_postcode ?? '',
            'owner_country' => $data->owner_country ?? '',
            'owner_phone' => $data->owner_phone ?? '',
        ];

        $result = $defensive->renew($contactData);

        if ($result['success']) {
            return 'success';
        }

        return $result['error'] ?? $result['message'] ?? 'Unknown error';

    } catch (\Exception $e) {
        logModuleCall('asciodefensive', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        return $e->getMessage();
    }
}

/**
 * Suspend account (not supported).
 *
 * @param array $params
 * @return string
 */
function asciodefensive_SuspendAccount(array $params): string
{
    return 'Suspension not supported for defensive registrations';
}

/**
 * Unsuspend account (not supported).
 *
 * @param array $params
 * @return string
 */
function asciodefensive_UnsuspendAccount(array $params): string
{
    return 'Unsuspension not supported for defensive registrations';
}

/**
 * Admin custom button actions.
 *
 * @return array
 */
function asciodefensive_AdminCustomButtonArray(): array
{
    return [
        'Refresh Status' => 'refreshStatus',
    ];
}

/**
 * Refresh status from Ascio API.
 *
 * @param array $params
 * @return string
 */
function asciodefensive_refreshStatus(array $params): string
{
    try {
        $coreParams = new Params($params);
        $defensive = new Defensive($coreParams);
        $data = $defensive->readDb();

        if (empty($data->handle)) {
            return 'No handle found - order may still be pending';
        }

        $info = $defensive->getInfo($data->handle);

        Capsule::table('mod_ascio_defensive')
            ->where('whmcs_service_id', $params['serviceid'])
            ->update([
                'status' => 'Active',
                'expire_date' => $info->getExpDate(),
            ]);

        return 'success';

    } catch (\Exception $e) {
        return $e->getMessage();
    }
}

/**
 * Admin services tab fields.
 *
 * @param array $params
 * @return array
 */
function asciodefensive_AdminServicesTabFields(array $params): array
{
    try {
        $data = Capsule::table('mod_ascio_defensive')
            ->where('whmcs_service_id', $params['serviceid'])
            ->first();

        if (!$data) {
            return ['Status' => 'Not initialized'];
        }

        return [
            'Defensive Domain' => $data->name,
            'Mark Handle' => $data->mark_handle ?? 'N/A',
            'Order ID' => $data->order_id,
            'Handle' => $data->handle ?? 'Pending',
            'Status' => $data->status,
            'Expiry' => $data->expire_date ?? 'N/A',
        ];

    } catch (\Exception $e) {
        return ['Error' => $e->getMessage()];
    }
}

/**
 * Client area output.
 *
 * @param array $params
 * @return array
 */
function asciodefensive_ClientArea(array $params): array
{
    try {
        $data = Capsule::table('mod_ascio_defensive')
            ->where('whmcs_service_id', $params['serviceid'])
            ->first();

        if (!$data) {
            return [
                'tabOverviewReplacementTemplate' => 'templates/error.tpl',
                'templateVariables' => ['error' => 'Service not initialized'],
            ];
        }

        return [
            'tabOverviewReplacementTemplate' => 'templates/overview.tpl',
            'templateVariables' => [
                'name' => $data->name,
                'markHandle' => $data->mark_handle,
                'status' => $data->status,
                'expiry' => $data->expire_date,
                'handle' => $data->handle,
            ],
        ];

    } catch (\Exception $e) {
        return [
            'tabOverviewReplacementTemplate' => 'templates/error.tpl',
            'templateVariables' => ['error' => $e->getMessage()],
        ];
    }
}

/**
 * Convert WHMCS billing cycle to period.
 *
 * @param array $params
 * @return int
 */
function asciodefensive_GetPeriod(array $params): int
{
    return match ($params['billingcycle'] ?? 'Annually') {
        'Annually' => 1,
        'Biennially' => 2,
        'Triennially' => 3,
        default => 1,
    };
}

/**
 * Ensure database table exists.
 *
 * Creates table lazily on first use (standard WHMCS pattern for server modules).
 *
 * @return bool
 */
function asciodefensive_EnsureTable(): bool
{
    try {
        if (!Capsule::schema()->hasTable('mod_ascio_defensive')) {
            Capsule::schema()->create('mod_ascio_defensive', function ($table) {
                $table->increments('id');
                $table->string('order_id', 50);
                $table->integer('whmcs_service_id');
                $table->integer('user_id');
                $table->string('handle', 50)->nullable();
                $table->string('name', 255)->comment('Defensive domain name');
                $table->string('mark_handle', 50)->nullable()->comment('Associated TMCH mark handle');
                $table->string('auth_info', 255)->nullable();
                $table->string('encoding', 50)->nullable();
                $table->integer('period')->default(1);
                $table->string('status', 50)->default('Pending');
                $table->integer('code')->nullable();
                $table->text('message')->nullable();
                $table->text('errors')->nullable();
                $table->dateTime('expire_date')->nullable();
                $table->string('owner_name', 255)->nullable();
                $table->string('owner_email', 255)->nullable();
                $table->string('owner_company', 255)->nullable();
                $table->string('owner_address1', 255)->nullable();
                $table->string('owner_address2', 255)->nullable();
                $table->string('owner_city', 100)->nullable();
                $table->string('owner_state', 100)->nullable();
                $table->string('owner_postcode', 50)->nullable();
                $table->string('owner_country', 10)->nullable();
                $table->string('owner_phone', 50)->nullable();
                $table->string('admin_name', 255)->nullable();
                $table->string('admin_email', 255)->nullable();
                $table->string('admin_company', 255)->nullable();
                $table->string('admin_phone', 50)->nullable();
                $table->string('tech_name', 255)->nullable();
                $table->string('tech_email', 255)->nullable();
                $table->string('tech_company', 255)->nullable();
                $table->string('tech_phone', 50)->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->nullable();
                $table->index('order_id', 'idx_order_id');
                $table->index('whmcs_service_id', 'idx_service_id');
                $table->index('user_id', 'idx_user_id');
                $table->index('status', 'idx_status');
                $table->index('mark_handle', 'idx_mark_handle');
            });
        }
        return true;
    } catch (\Exception $e) {
        logModuleCall('asciodefensive', 'EnsureTable', [], $e->getMessage(), $e->getTraceAsString());
        return false;
    }
}
