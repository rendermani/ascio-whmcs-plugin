<?php

/**
 * Ascio Domain Monitoring WHMCS Module
 *
 * Provides domain/trademark monitoring services via the Ascio NameWatch API.
 * Monitors for similar domain registrations and trademark conflicts.
 *
 * @copyright Copyright (c) Tucows Inc.
 */

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

// Load dependencies
require_once __DIR__ . '/../core/lib/autoload.php';
require_once __DIR__ . '/lib/Monitoring.php';
require_once __DIR__ . '/lib/MonitoringCallback.php';

use Ascio\Core\Params;
use Ascio\Monitoring\Monitoring;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Module metadata.
 *
 * @return array
 */
function asciomonitoring_MetaData(): array
{
    return [
        'DisplayName' => 'Ascio Domain Monitoring',
        'APIVersion' => '1.1',
        'RequiresServer' => false,
    ];
}

/**
 * Product configuration options.
 *
 * @return array
 */
function asciomonitoring_ConfigOptions(): array
{
    return [
        'Tier' => [
            'Type' => 'dropdown',
            'Options' => '1,2,3,4,5',
            'Default' => '1',
            'Description' => 'Monitoring tier level (1=basic, 5=comprehensive)',
        ],
        'NotificationFrequency' => [
            'Type' => 'dropdown',
            'Options' => 'Daily,Weekly,Monthly',
            'Default' => 'Daily',
            'Description' => 'How often to send monitoring reports',
        ],
    ];
}

/**
 * Provision a new monitoring service.
 *
 * @param array $params WHMCS parameters
 * @return string "success" or error message
 */
function asciomonitoring_CreateAccount(array $params): string
{
    try {
        // Ensure table exists
        if (!asciomonitoring_EnsureTable()) {
            return 'Database table not found. Please run module installation.';
        }

        $coreParams = new Params($params);
        $monitoring = new Monitoring($coreParams);

        // Get form data from custom fields or defaults
        $formData = [
            'name' => $params['domain'] ?? $params['customfields']['MonitoredTerm'] ?? '',
            'tier' => (int)($params['configoption1'] ?? 1),
            'notification_frequency' => $params['configoption2'] ?? 'Daily',
            'email_notification_1' => $params['clientsdetails']['email'] ?? '',
            'period' => asciomonitoring_GetPeriod($params),
        ];

        // Owner contact from client details
        $contactData = [
            'name' => $params['clientsdetails']['firstname'] . ' ' . $params['clientsdetails']['lastname'],
            'email' => $params['clientsdetails']['email'],
            'company' => $params['clientsdetails']['companyname'] ?? '',
            'address1' => $params['clientsdetails']['address1'] ?? '',
            'address2' => $params['clientsdetails']['address2'] ?? '',
            'city' => $params['clientsdetails']['city'] ?? '',
            'state' => $params['clientsdetails']['state'] ?? '',
            'postcode' => $params['clientsdetails']['postcode'] ?? '',
            'country' => $params['clientsdetails']['countrycode'] ?? '',
            'phone' => $params['clientsdetails']['phonenumber'] ?? '',
        ];

        // Initialize record
        $monitoring->fromForm(array_merge($formData, [
            'owner_name' => $contactData['name'],
            'owner_email' => $contactData['email'],
            'owner_company' => $contactData['company'],
            'owner_address1' => $contactData['address1'],
            'owner_address2' => $contactData['address2'],
            'owner_city' => $contactData['city'],
            'owner_state' => $contactData['state'],
            'owner_postcode' => $contactData['postcode'],
            'owner_country' => $contactData['country'],
            'owner_phone' => $contactData['phone'],
        ]));
        $monitoring->writeDb();

        // Submit order
        $result = $monitoring->register($contactData);

        if ($result['success']) {
            return 'success';
        }

        return $result['error'] ?? $result['message'] ?? 'Unknown error';

    } catch (\Exception $e) {
        logModuleCall('asciomonitoring', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        return $e->getMessage();
    }
}

/**
 * Terminate monitoring service.
 *
 * @param array $params WHMCS parameters
 * @return string "success" or error message
 */
function asciomonitoring_TerminateAccount(array $params): string
{
    try {
        $coreParams = new Params($params);
        $monitoring = new Monitoring($coreParams);

        $result = $monitoring->terminate();

        if ($result['success']) {
            return 'success';
        }

        return $result['error'] ?? $result['message'] ?? 'Unknown error';

    } catch (\Exception $e) {
        logModuleCall('asciomonitoring', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        return $e->getMessage();
    }
}

/**
 * Renew monitoring service.
 *
 * @param array $params WHMCS parameters
 * @return string "success" or error message
 */
function asciomonitoring_RenewAccount(array $params): string
{
    try {
        $coreParams = new Params($params);
        $monitoring = new Monitoring($coreParams);

        $data = $monitoring->readDb();
        $contactData = [
            'name' => $data->owner_name ?? '',
            'email' => $data->owner_email ?? '',
            'company' => $data->owner_company ?? '',
            'address1' => $data->owner_address1 ?? '',
            'address2' => $data->owner_address2 ?? '',
            'city' => $data->owner_city ?? '',
            'state' => $data->owner_state ?? '',
            'postcode' => $data->owner_postcode ?? '',
            'country' => $data->owner_country ?? '',
            'phone' => $data->owner_phone ?? '',
        ];

        $result = $monitoring->renew($contactData);

        if ($result['success']) {
            return 'success';
        }

        return $result['error'] ?? $result['message'] ?? 'Unknown error';

    } catch (\Exception $e) {
        logModuleCall('asciomonitoring', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        return $e->getMessage();
    }
}

/**
 * Suspend account (not supported by Ascio).
 *
 * @param array $params
 * @return string
 */
function asciomonitoring_SuspendAccount(array $params): string
{
    return 'Suspension not supported for monitoring services';
}

/**
 * Unsuspend account (not supported by Ascio).
 *
 * @param array $params
 * @return string
 */
function asciomonitoring_UnsuspendAccount(array $params): string
{
    return 'Unsuspension not supported for monitoring services';
}

/**
 * Admin custom button actions.
 *
 * @return array
 */
function asciomonitoring_AdminCustomButtonArray(): array
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
function asciomonitoring_refreshStatus(array $params): string
{
    try {
        $coreParams = new Params($params);
        $monitoring = new Monitoring($coreParams);
        $data = $monitoring->readDb();

        if (empty($data->handle)) {
            return 'No handle found - order may still be pending';
        }

        $info = $monitoring->getInfo($data->handle);

        Capsule::table('mod_ascio_monitoring')
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
function asciomonitoring_AdminServicesTabFields(array $params): array
{
    try {
        $data = Capsule::table('mod_ascio_monitoring')
            ->where('whmcs_service_id', $params['serviceid'])
            ->first();

        if (!$data) {
            return ['Status' => 'Not initialized'];
        }

        return [
            'Monitored Term' => $data->name,
            'Tier' => $data->tier,
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
function asciomonitoring_ClientArea(array $params): array
{
    try {
        $data = Capsule::table('mod_ascio_monitoring')
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
                'tier' => $data->tier,
                'status' => $data->status,
                'expiry' => $data->expire_date,
                'frequency' => $data->notification_frequency,
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
function asciomonitoring_GetPeriod(array $params): int
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
function asciomonitoring_EnsureTable(): bool
{
    try {
        if (!Capsule::schema()->hasTable('mod_ascio_monitoring')) {
            Capsule::schema()->create('mod_ascio_monitoring', function ($table) {
                $table->increments('id');
                $table->string('order_id', 50);
                $table->integer('whmcs_service_id');
                $table->integer('user_id');
                $table->string('handle', 50)->nullable();
                $table->string('name', 255)->comment('Monitored term/name');
                $table->integer('tier')->default(1)->comment('Monitoring tier (1-5)');
                $table->string('notification_frequency', 50)->default('Daily');
                $table->string('email_notification_1', 255)->nullable();
                $table->string('email_notification_2', 255)->nullable();
                $table->string('email_notification_3', 255)->nullable();
                $table->string('email_notification_4', 255)->nullable();
                $table->string('email_notification_5', 255)->nullable();
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
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->nullable();
                $table->index('order_id', 'idx_order_id');
                $table->index('whmcs_service_id', 'idx_service_id');
                $table->index('user_id', 'idx_user_id');
                $table->index('status', 'idx_status');
            });
        }
        return true;
    } catch (\Exception $e) {
        logModuleCall('asciomonitoring', 'EnsureTable', [], $e->getMessage(), $e->getTraceAsString());
        return false;
    }
}
