<?php

/**
 * Ascio TMCH (Trademark Clearinghouse) WHMCS Module
 *
 * Provides trademark registration and claims services via the Ascio API.
 * Supports Sunrise and Claims periods for new TLD launches.
 *
 * @copyright Copyright (c) Tucows Inc.
 */

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

// Load dependencies
require_once __DIR__ . '/../core/lib/autoload.php';
require_once __DIR__ . '/lib/Tmch.php';
require_once __DIR__ . '/lib/TmchCallback.php';

use Ascio\Core\Params;
use Ascio\Tmch\Tmch;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Module metadata.
 *
 * @return array
 */
function asciotmch_MetaData(): array
{
    return [
        'DisplayName' => 'Ascio TMCH (Trademark Clearinghouse)',
        'APIVersion' => '1.1',
        'RequiresServer' => false,
    ];
}

/**
 * Product configuration options.
 *
 * @return array
 */
function asciotmch_ConfigOptions(): array
{
    return [
        'MarkType' => [
            'Type' => 'dropdown',
            'Options' => 'Trademark,TreatyOrStatute,CourtValidated',
            'Default' => 'Trademark',
            'Description' => 'Type of trademark mark',
        ],
        'ServiceType' => [
            'Type' => 'dropdown',
            'Options' => 'Sunrise,Claims',
            'Default' => 'Sunrise',
            'Description' => 'TMCH service type',
        ],
    ];
}

/**
 * Provision a new TMCH mark.
 *
 * @param array $params WHMCS parameters
 * @return string "success" or error message
 */
function asciotmch_CreateAccount(array $params): string
{
    try {
        // Ensure table exists
        if (!asciotmch_EnsureTable()) {
            return 'Database table not found. Please run module installation.';
        }

        $coreParams = new Params($params);
        $tmch = new Tmch($coreParams);

        // Get form data
        $formData = [
            'mark_name' => $params['domain'] ?? $params['customfields']['MarkName'] ?? '',
            'mark_type' => $params['configoption1'] ?? 'Trademark',
            'service_type' => $params['configoption2'] ?? 'Sunrise',
            'goods_and_services' => $params['customfields']['GoodsAndServices'] ?? '',
            'application_id' => $params['customfields']['ApplicationId'] ?? '',
            'registration_number' => $params['customfields']['RegistrationNumber'] ?? '',
            'jurisdiction' => $params['customfields']['Jurisdiction'] ?? '',
            'notification_frequency' => 'Daily',
            'claim_email_1' => $params['clientsdetails']['email'] ?? '',
            'period' => asciotmch_GetPeriod($params),
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
        $tmch->fromForm(array_merge($formData, $contactData));
        $tmch->writeDb();

        // Submit order
        $result = $tmch->register($contactData);

        if ($result['success']) {
            return 'success';
        }

        return $result['error'] ?? $result['message'] ?? 'Unknown error';

    } catch (\Exception $e) {
        logModuleCall('asciotmch', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        return $e->getMessage();
    }
}

/**
 * Terminate TMCH mark.
 *
 * @param array $params WHMCS parameters
 * @return string "success" or error message
 */
function asciotmch_TerminateAccount(array $params): string
{
    try {
        $coreParams = new Params($params);
        $tmch = new Tmch($coreParams);

        $result = $tmch->terminate();

        if ($result['success']) {
            return 'success';
        }

        return $result['error'] ?? $result['message'] ?? 'Unknown error';

    } catch (\Exception $e) {
        logModuleCall('asciotmch', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        return $e->getMessage();
    }
}

/**
 * Renew TMCH mark.
 *
 * @param array $params WHMCS parameters
 * @return string "success" or error message
 */
function asciotmch_RenewAccount(array $params): string
{
    try {
        $coreParams = new Params($params);
        $tmch = new Tmch($coreParams);

        $data = $tmch->readDb();
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

        $result = $tmch->renew($contactData);

        if ($result['success']) {
            return 'success';
        }

        return $result['error'] ?? $result['message'] ?? 'Unknown error';

    } catch (\Exception $e) {
        logModuleCall('asciotmch', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        return $e->getMessage();
    }
}

/**
 * Suspend account (not supported).
 *
 * @param array $params
 * @return string
 */
function asciotmch_SuspendAccount(array $params): string
{
    return 'Suspension not supported for TMCH marks';
}

/**
 * Unsuspend account (not supported).
 *
 * @param array $params
 * @return string
 */
function asciotmch_UnsuspendAccount(array $params): string
{
    return 'Unsuspension not supported for TMCH marks';
}

/**
 * Admin custom button actions.
 *
 * @return array
 */
function asciotmch_AdminCustomButtonArray(): array
{
    return [
        'Refresh Status' => 'refreshStatus',
        'Upload Documents' => 'uploadDocuments',
    ];
}

/**
 * Refresh status from Ascio API.
 *
 * @param array $params
 * @return string
 */
function asciotmch_refreshStatus(array $params): string
{
    try {
        $coreParams = new Params($params);
        $tmch = new Tmch($coreParams);
        $data = $tmch->readDb();

        if (empty($data->handle)) {
            return 'No handle found - order may still be pending';
        }

        $info = $tmch->getInfo($data->handle);

        $updateData = [
            'status' => 'Active',
            'expire_date' => $info->getExpDate(),
        ];

        if (method_exists($info, 'getMarkId')) {
            $updateData['mark_id'] = $info->getMarkId();
        }

        Capsule::table('mod_ascio_tmch')
            ->where('whmcs_service_id', $params['serviceid'])
            ->update($updateData);

        return 'success';

    } catch (\Exception $e) {
        return $e->getMessage();
    }
}

/**
 * Upload documents button handler.
 * This returns a form for document upload.
 *
 * @param array $params
 * @return string
 */
function asciotmch_uploadDocuments(array $params): string
{
    // This would typically redirect to a document upload form
    // For now, return a message
    return 'Document upload requires client area form submission';
}

/**
 * Admin services tab fields.
 *
 * @param array $params
 * @return array
 */
function asciotmch_AdminServicesTabFields(array $params): array
{
    try {
        $data = Capsule::table('mod_ascio_tmch')
            ->where('whmcs_service_id', $params['serviceid'])
            ->first();

        if (!$data) {
            return ['Status' => 'Not initialized'];
        }

        return [
            'Mark Name' => $data->mark_name,
            'Mark Type' => $data->mark_type,
            'Service Type' => $data->service_type,
            'Order ID' => $data->order_id,
            'Handle' => $data->handle ?? 'Pending',
            'Mark ID' => $data->mark_id ?? 'N/A',
            'Status' => $data->status,
            'Expiry' => $data->expire_date ?? 'N/A',
            'Documents Uploaded' => $data->documents_uploaded ? 'Yes' : 'No',
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
function asciotmch_ClientArea(array $params): array
{
    try {
        $data = Capsule::table('mod_ascio_tmch')
            ->where('whmcs_service_id', $params['serviceid'])
            ->first();

        if (!$data) {
            return [
                'tabOverviewReplacementTemplate' => 'templates/error.tpl',
                'templateVariables' => ['error' => 'Service not initialized'],
            ];
        }

        // Handle document upload form submission
        if (isset($_POST['upload_document']) && !empty($_FILES['document'])) {
            $result = asciotmch_HandleDocumentUpload($params, $_POST, $_FILES['document']);
            if (!$result['success']) {
                return [
                    'tabOverviewReplacementTemplate' => 'templates/error.tpl',
                    'templateVariables' => ['error' => $result['error']],
                ];
            }
        }

        return [
            'tabOverviewReplacementTemplate' => 'templates/overview.tpl',
            'templateVariables' => [
                'markName' => $data->mark_name,
                'markType' => $data->mark_type,
                'serviceType' => $data->service_type,
                'markId' => $data->mark_id,
                'status' => $data->status,
                'expiry' => $data->expire_date,
                'handle' => $data->handle,
                'documentsUploaded' => $data->documents_uploaded,
                'needsDocuments' => !$data->documents_uploaded && $data->status === 'Pending_End_User_Action',
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
 * Handle document upload from client area.
 *
 * @param array $params WHMCS params
 * @param array $post POST data
 * @param array $file Uploaded file data
 * @return array Result
 */
function asciotmch_HandleDocumentUpload(array $params, array $post, array $file): array
{
    try {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'File upload failed'];
        }

        $coreParams = new Params($params);
        $tmch = new Tmch($coreParams);

        $docType = $post['doc_type'] ?? 'TrademarkCopy';
        $content = file_get_contents($file['tmp_name']);

        $result = $tmch->uploadDocumentation([
            [
                'type' => $docType,
                'filename' => $file['name'],
                'content' => $content,
            ]
        ]);

        return $result;

    } catch (\Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Convert WHMCS billing cycle to period.
 *
 * @param array $params
 * @return int
 */
function asciotmch_GetPeriod(array $params): int
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
 * Creates tables lazily on first use (standard WHMCS pattern for server modules).
 *
 * @return bool
 */
function asciotmch_EnsureTable(): bool
{
    try {
        if (!Capsule::schema()->hasTable('mod_ascio_tmch')) {
            Capsule::schema()->create('mod_ascio_tmch', function ($table) {
                $table->increments('id');
                $table->string('order_id', 50);
                $table->integer('whmcs_service_id');
                $table->integer('user_id');
                $table->string('handle', 50)->nullable();
                $table->string('mark_name', 255)->comment('Trademark name');
                $table->string('mark_id', 100)->nullable()->comment('TMCH Mark ID');
                $table->string('mark_type', 50)->default('Trademark');
                $table->string('service_type', 50)->default('Sunrise');
                $table->text('goods_and_services')->nullable();
                $table->text('labels')->nullable()->comment('JSON array of domain labels');
                $table->string('notification_frequency', 50)->nullable()->default('Daily');
                $table->string('claim_email_1', 255)->nullable();
                $table->string('claim_email_2', 255)->nullable();
                $table->string('claim_email_3', 255)->nullable();
                $table->string('claim_email_4', 255)->nullable();
                $table->string('claim_email_5', 255)->nullable();
                $table->integer('period')->default(1);
                $table->string('status', 50)->default('Pending');
                $table->integer('code')->nullable();
                $table->text('message')->nullable();
                $table->text('errors')->nullable();
                $table->dateTime('expire_date')->nullable();
                $table->string('auth_info', 255)->nullable();
                $table->string('application_id', 100)->nullable();
                $table->string('registration_number', 100)->nullable();
                $table->date('application_date')->nullable();
                $table->date('registration_date')->nullable();
                $table->string('jurisdiction', 50)->nullable();
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
                $table->boolean('documents_uploaded')->default(false);
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->nullable();
                $table->index('order_id', 'idx_order_id');
                $table->index('whmcs_service_id', 'idx_service_id');
                $table->index('user_id', 'idx_user_id');
                $table->index('status', 'idx_status');
                $table->index('mark_id', 'idx_mark_id');
            });
        }

        if (!Capsule::schema()->hasTable('mod_ascio_tmch_documents')) {
            Capsule::schema()->create('mod_ascio_tmch_documents', function ($table) {
                $table->increments('id');
                $table->integer('tmch_id')->unsigned();
                $table->string('doc_type', 50)->comment('TrademarkCopy, ProofOfUse, Declaration, etc.');
                $table->string('filename', 255);
                $table->timestamp('uploaded_at')->useCurrent();
                $table->string('status', 50)->default('Pending');
                $table->text('message')->nullable();
                $table->index('tmch_id', 'idx_tmch_id');
                $table->foreign('tmch_id')->references('id')->on('mod_ascio_tmch')->onDelete('cascade');
            });
        }

        return true;
    } catch (\Exception $e) {
        logModuleCall('asciotmch', 'EnsureTable', [], $e->getMessage(), $e->getTraceAsString());
        return false;
    }
}
