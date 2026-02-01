<?php

namespace Ascio\Tmch;

use Ascio\Core\AscioClient;
use Ascio\Core\AscioApiException;
use Ascio\Core\Contracts\AscioClientInterface;
use Ascio\Core\Contracts\DatabaseInterface;
use Ascio\Core\Contracts\ParamsInterface;
use Ascio\Core\CapsuleDatabase;
use Ascio\Core\OrderType;
use Ascio\Core\ResponseHandler;
use ascio\v3 as v3;

/**
 * TMCH (Trademark Clearinghouse) business logic.
 * Handles registration, renewal, and management of trademark records.
 */
class Tmch
{
    /** @var AscioClientInterface API client */
    protected AscioClientInterface $client;

    /** @var DatabaseInterface Database adapter */
    protected DatabaseInterface $db;

    /** @var ParamsInterface Parameters */
    protected ParamsInterface $params;

    /** @var ResponseHandler Response handler */
    protected ResponseHandler $responseHandler;

    /** @var int|null WHMCS service ID */
    protected ?int $serviceId;

    /** @var int|null WHMCS user ID */
    protected ?int $userId;

    /** @var array Order data */
    protected array $data = [];

    /** @var bool Whether data has been loaded from DB */
    protected bool $hasDbData = false;

    /** @var array Valid mark types */
    public const MARK_TYPES = ['Trademark', 'TreatyOrStatute', 'CourtValidated'];

    /** @var array Valid service types */
    public const SERVICE_TYPES = ['Sunrise', 'Claims'];

    /**
     * @param ParamsInterface $params Credential provider
     * @param AscioClientInterface|null $client Optional API client
     * @param DatabaseInterface|null $db Optional database adapter
     */
    public function __construct(
        ParamsInterface $params,
        ?AscioClientInterface $client = null,
        ?DatabaseInterface $db = null
    ) {
        $this->params = $params;
        $this->client = $client ?? new AscioClient($params);
        $this->db = $db ?? new CapsuleDatabase();
        $this->responseHandler = new ResponseHandler('asciotmch');
        $this->serviceId = $params->getServiceId();
        $this->userId = $params->getUserId();
    }

    /**
     * Register a new TMCH mark.
     *
     * @param array $contactData Contact information
     * @param array $documents Optional document data
     * @return array Result with order_id, status, etc.
     */
    public function register(array $contactData, array $documents = []): array
    {
        return $this->submit($contactData, OrderType::REGISTER, $documents);
    }

    /**
     * Renew an existing TMCH mark.
     *
     * @param array $contactData Contact information
     * @return array Result
     */
    public function renew(array $contactData): array
    {
        return $this->submit($contactData, OrderType::RENEW);
    }

    /**
     * Terminate/delete a TMCH mark.
     *
     * @return array Result
     */
    public function terminate(): array
    {
        return $this->submit([], OrderType::DELETE);
    }

    /**
     * Submit a TMCH order to Ascio.
     *
     * @param array $contactData Contact information
     * @param string $orderType Order type
     * @param array $documents Document data
     * @return array Result
     */
    protected function submit(array $contactData, string $orderType, array $documents = []): array
    {
        $data = $this->readDb();

        // Build owner contact
        $owner = $this->buildOwner($contactData);

        // Build mark object based on type
        $mark = $this->buildMark($data, $owner);

        // Build order request
        $orderRequest = new v3\MarkOrderRequest($orderType);
        $orderRequest->setType($orderType);
        $orderRequest->setPeriod($data->period ?? 1);
        $orderRequest->setTransactionComment('WHMCS TMCH Module');
        $orderRequest->setMark($mark);

        // Add documents if provided
        if (!empty($documents)) {
            $orderRequest->setDocuments($this->buildDocuments($documents));
        }

        try {
            $response = $this->client->createOrder($orderRequest);
            $result = $response->CreateOrderResult;

            if ($result->getResultCode() === 200) {
                $orderInfo = $result->getOrderInfo();
                $orderId = $this->formatOrderId($orderInfo->getOrderId());

                $updateData = [
                    'code' => $result->getResultCode(),
                    'message' => $result->getResultMessage(),
                    'status' => $orderInfo->getStatus(),
                    'order_id' => $orderId,
                    'errors' => null,
                ];
                $this->writeStatus($updateData);

                return array_merge($updateData, ['success' => true]);

            } else {
                $errors = $this->responseHandler->extractErrors($result);
                $updateData = [
                    'code' => $result->getResultCode(),
                    'message' => $result->getResultMessage(),
                    'status' => $result->getResultMessage(),
                    'errors' => json_encode($errors),
                ];
                $this->writeStatus($updateData);

                return array_merge($updateData, ['success' => false, 'error' => $this->responseHandler->formatErrors($errors)]);
            }

        } catch (\SoapFault $e) {
            $this->responseHandler->logCall('CreateOrder', $orderRequest, $e, $e->getMessage());
            return [
                'success' => false,
                'error' => 'Temporary error. Please retry later: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Build mark object based on type.
     *
     * @param object $data Database data
     * @param v3\Registrant $owner Owner contact
     * @return v3\AbstractMark
     */
    protected function buildMark($data, v3\Registrant $owner): v3\AbstractMark
    {
        $markType = $data->mark_type ?? 'Trademark';

        $mark = match ($markType) {
            'TreatyOrStatute' => new v3\TreatyOrStatuteMark(),
            'CourtValidated' => new v3\CourtValidatedMark(),
            default => new v3\Trademark(),
        };

        // Common fields
        $mark->setMarkName($data->mark_name);
        $mark->setOwner($owner);
        $mark->setGoodsAndServicesDescription($data->goods_and_services ?? '');

        // Service type
        if (!empty($data->service_type)) {
            $mark->setServiceType($data->service_type);
        }

        // Notification settings
        if (!empty($data->notification_frequency)) {
            $mark->setNotificationFrequency($data->notification_frequency);
        }

        // Claim notification emails
        if (!empty($data->claim_email_1)) {
            $mark->setClaimEmailNotification1($data->claim_email_1);
        }
        if (!empty($data->claim_email_2)) {
            $mark->setClaimEmailNotification2($data->claim_email_2);
        }
        if (!empty($data->claim_email_3)) {
            $mark->setClaimEmailNotification3($data->claim_email_3);
        }
        if (!empty($data->claim_email_4)) {
            $mark->setClaimEmailNotification4($data->claim_email_4);
        }
        if (!empty($data->claim_email_5)) {
            $mark->setClaimEmailNotification5($data->claim_email_5);
        }

        // Labels
        if (!empty($data->labels)) {
            $labels = json_decode($data->labels, true);
            if (is_array($labels)) {
                $arrayOfString = new v3\ArrayOfstring();
                foreach ($labels as $label) {
                    $arrayOfString->addString($label);
                }
                $mark->setLabels($arrayOfString);
            }
        }

        // Trademark-specific fields
        if ($mark instanceof v3\Trademark) {
            if (!empty($data->application_id)) {
                $mark->setApplicationId($data->application_id);
            }
            if (!empty($data->registration_number)) {
                $mark->setRegistrationNumber($data->registration_number);
            }
            if (!empty($data->application_date)) {
                $mark->setApplicationDate(new \DateTime($data->application_date));
            }
            if (!empty($data->registration_date)) {
                $mark->setRegistrationDate(new \DateTime($data->registration_date));
            }
            if (!empty($data->jurisdiction)) {
                $mark->setJurisdiction($data->jurisdiction);
            }
        }

        // For renewal/delete, set handle
        if (!empty($data->handle)) {
            $mark->setHandle($data->handle);
        }

        return $mark;
    }

    /**
     * Build owner Registrant object.
     *
     * @param array $contactData
     * @return v3\Registrant
     */
    protected function buildOwner(array $contactData): v3\Registrant
    {
        $owner = new v3\Registrant();

        $owner->setName($contactData['name'] ?? $contactData['owner_name'] ?? '');
        $owner->setEmail($contactData['email'] ?? $contactData['owner_email'] ?? '');
        $owner->setOrgName($contactData['company'] ?? $contactData['owner_company'] ?? '');
        $owner->setAddress1($contactData['address1'] ?? $contactData['owner_address1'] ?? '');
        $owner->setAddress2($contactData['address2'] ?? $contactData['owner_address2'] ?? '');
        $owner->setCity($contactData['city'] ?? $contactData['owner_city'] ?? '');
        $owner->setState($contactData['state'] ?? $contactData['owner_state'] ?? '');
        $owner->setPostalCode($contactData['postcode'] ?? $contactData['owner_postcode'] ?? '');
        $owner->setCountryCode($contactData['country'] ?? $contactData['owner_country'] ?? '');
        $owner->setPhone($contactData['phone'] ?? $contactData['owner_phone'] ?? '');

        return $owner;
    }

    /**
     * Build documents array for upload.
     *
     * @param array $documents
     * @return v3\ArrayOfMarkOrderDocument
     */
    protected function buildDocuments(array $documents): v3\ArrayOfMarkOrderDocument
    {
        $arrayOfDocs = new v3\ArrayOfMarkOrderDocument();

        foreach ($documents as $doc) {
            $markDoc = new v3\MarkOrderDocument();
            $markDoc->setDocType($doc['type']);

            $attachment = new v3\Attachment();
            $attachment->setData(base64_encode($doc['content']));
            $attachment->setFilename($doc['filename']);
            $markDoc->setAttachment($attachment);

            $arrayOfDocs->addMarkOrderDocument($markDoc);
        }

        return $arrayOfDocs;
    }

    /**
     * Upload documentation for the mark.
     *
     * @param array $documents Array of ['type' => string, 'filename' => string, 'content' => string]
     * @return array Result
     */
    public function uploadDocumentation(array $documents): array
    {
        $data = $this->readDb();

        if (empty($data->handle)) {
            return [
                'success' => false,
                'error' => 'Cannot upload documents: mark handle not yet assigned',
            ];
        }

        $request = new v3\UploadDocumentationRequest();
        $request->setHandle($data->handle);
        $request->setObjectType('MarkType');
        $request->setDocuments($this->buildDocuments($documents));

        try {
            $response = $this->client->uploadDocumentation($request);
            $result = $response->UploadDocumentationResult;

            if ($result->getResultCode() === 200) {
                $this->writeStatus(['documents_uploaded' => 1]);
                return ['success' => true];
            } else {
                $errors = $this->responseHandler->extractErrors($result);
                return [
                    'success' => false,
                    'error' => $this->responseHandler->formatErrors($errors),
                ];
            }

        } catch (\SoapFault $e) {
            $this->responseHandler->logCall('UploadDocumentation', $request, $e, $e->getMessage());
            return [
                'success' => false,
                'error' => 'Temporary error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Format order ID with environment prefix.
     *
     * @param string|int $orderId
     * @return string
     */
    protected function formatOrderId($orderId): string
    {
        if (str_starts_with((string)$orderId, 'TEST') || str_starts_with((string)$orderId, 'A')) {
            return (string)$orderId;
        }
        return $this->params->isTestMode() ? "TEST{$orderId}" : "A{$orderId}";
    }

    /**
     * Read data from database.
     *
     * @return object|null
     */
    public function readDb(): ?object
    {
        if ($this->hasDbData) {
            return (object)$this->data;
        }

        $data = $this->db->first(
            'mod_ascio_tmch',
            ['*'],
            ['whmcs_service_id' => $this->serviceId]
        );

        if ($data) {
            $this->hasDbData = true;
            $this->data = (array)$data;
        }

        return $data;
    }

    /**
     * Write data to database.
     *
     * @return void
     */
    public function writeDb(): void
    {
        $this->readDb();

        if ($this->hasDbData) {
            $this->db->update(
                'mod_ascio_tmch',
                $this->data,
                ['whmcs_service_id' => $this->serviceId]
            );
        } else {
            $this->data['whmcs_service_id'] = $this->serviceId;
            $this->data['user_id'] = $this->userId;
            $this->db->insert('mod_ascio_tmch', $this->data);
            $this->hasDbData = true;
        }
    }

    /**
     * Write status update.
     *
     * @param array $data
     */
    protected function writeStatus(array $data): void
    {
        $this->db->update(
            'mod_ascio_tmch',
            $data,
            ['whmcs_service_id' => $this->serviceId]
        );
    }

    /**
     * Set data from form input.
     *
     * @param array $formData
     * @return self
     */
    public function fromForm(array $formData): self
    {
        $this->data = array_merge($this->data, [
            'mark_name' => $formData['mark_name'] ?? '',
            'mark_type' => $formData['mark_type'] ?? 'Trademark',
            'service_type' => $formData['service_type'] ?? 'Sunrise',
            'goods_and_services' => $formData['goods_and_services'] ?? '',
            'labels' => isset($formData['labels']) ? json_encode($formData['labels']) : null,
            'notification_frequency' => $formData['notification_frequency'] ?? 'Daily',
            'claim_email_1' => $formData['claim_email_1'] ?? '',
            'claim_email_2' => $formData['claim_email_2'] ?? '',
            'claim_email_3' => $formData['claim_email_3'] ?? '',
            'claim_email_4' => $formData['claim_email_4'] ?? '',
            'claim_email_5' => $formData['claim_email_5'] ?? '',
            'period' => (int)($formData['period'] ?? 1),
            'application_id' => $formData['application_id'] ?? '',
            'registration_number' => $formData['registration_number'] ?? '',
            'application_date' => $formData['application_date'] ?? null,
            'registration_date' => $formData['registration_date'] ?? null,
            'jurisdiction' => $formData['jurisdiction'] ?? '',
            'owner_name' => $formData['owner_name'] ?? '',
            'owner_email' => $formData['owner_email'] ?? '',
            'owner_company' => $formData['owner_company'] ?? '',
            'owner_address1' => $formData['owner_address1'] ?? '',
            'owner_address2' => $formData['owner_address2'] ?? '',
            'owner_city' => $formData['owner_city'] ?? '',
            'owner_state' => $formData['owner_state'] ?? '',
            'owner_postcode' => $formData['owner_postcode'] ?? '',
            'owner_country' => $formData['owner_country'] ?? '',
            'owner_phone' => $formData['owner_phone'] ?? '',
        ]);

        return $this;
    }

    /**
     * Get data for form display.
     *
     * @return array
     */
    public function toForm(): array
    {
        if (!$this->hasDbData) {
            $this->readDb();
        }
        return $this->data;
    }

    /**
     * Get mark info from API.
     *
     * @param string $handle Mark handle
     * @return object MarkInfo
     */
    public function getInfo(string $handle)
    {
        $response = $this->client->getMark($handle);
        $result = $this->responseHandler->handleResponse($response, 'GetMark');
        return $result->getMarkInfo();
    }

    /**
     * Get service ID.
     *
     * @return int|null
     */
    public function getServiceId(): ?int
    {
        return $this->serviceId;
    }

    /**
     * Set service ID.
     *
     * @param int $serviceId
     * @return self
     */
    public function setServiceId(int $serviceId): self
    {
        $this->serviceId = $serviceId;
        return $this;
    }

    /**
     * Get current data.
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }
}
