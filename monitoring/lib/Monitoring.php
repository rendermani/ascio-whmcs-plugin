<?php

namespace Ascio\Monitoring;

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
 * Domain Monitoring (NameWatch) business logic.
 * Handles registration, renewal, and termination of monitoring services.
 */
class Monitoring
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
        $this->responseHandler = new ResponseHandler('asciomonitoring');
        $this->serviceId = $params->getServiceId();
        $this->userId = $params->getUserId();
    }

    /**
     * Register a new domain monitoring service.
     *
     * @param array $contactData Owner contact information
     * @return array Result with order_id, status, etc.
     */
    public function register(array $contactData): array
    {
        return $this->submit($contactData, OrderType::REGISTER);
    }

    /**
     * Renew an existing monitoring service.
     *
     * @param array $contactData Owner contact information
     * @return array Result
     */
    public function renew(array $contactData): array
    {
        return $this->submit($contactData, OrderType::RENEW);
    }

    /**
     * Terminate/delete a monitoring service.
     *
     * @return array Result
     */
    public function terminate(): array
    {
        return $this->submit([], OrderType::DELETE);
    }

    /**
     * Submit a monitoring order to Ascio.
     *
     * @param array $contactData Contact information
     * @param string $orderType Order type
     * @return array Result
     */
    protected function submit(array $contactData, string $orderType): array
    {
        $data = $this->readDb();

        // Build owner contact
        $owner = $this->buildOwner($contactData);

        // Build NameWatch object
        $nameWatch = new v3\NameWatch();
        $nameWatch->setName($data->name);
        $nameWatch->setTier($data->tier);
        $nameWatch->setNotificationFrequency($data->notification_frequency);
        $nameWatch->setOwner($owner);

        // Set notification emails
        if (!empty($data->email_notification_1)) {
            $nameWatch->setEmailNotification1($data->email_notification_1);
        }
        if (!empty($data->email_notification_2)) {
            $nameWatch->setEmailNotification2($data->email_notification_2);
        }
        if (!empty($data->email_notification_3)) {
            $nameWatch->setEmailNotification3($data->email_notification_3);
        }
        if (!empty($data->email_notification_4)) {
            $nameWatch->setEmailNotification4($data->email_notification_4);
        }
        if (!empty($data->email_notification_5)) {
            $nameWatch->setEmailNotification5($data->email_notification_5);
        }

        // For renewal/delete, set handle
        if ($orderType !== OrderType::REGISTER && !empty($data->handle)) {
            $nameWatch->setHandle($data->handle);
        }

        // Build order request
        $orderRequest = new v3\NameWatchOrderRequest($orderType);
        $orderRequest->setType($orderType);
        $orderRequest->setPeriod($data->period ?? 1);
        $orderRequest->setTransactionComment('WHMCS Monitoring Module');
        $orderRequest->setNameWatch($nameWatch);

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
     * Build owner Registrant object.
     *
     * @param array $contactData
     * @return v3\Registrant
     */
    protected function buildOwner(array $contactData): v3\Registrant
    {
        $owner = new v3\Registrant();

        $owner->setName($contactData['name'] ?? '');
        $owner->setEmail($contactData['email'] ?? '');
        $owner->setOrgName($contactData['company'] ?? '');
        $owner->setAddress1($contactData['address1'] ?? '');
        $owner->setAddress2($contactData['address2'] ?? '');
        $owner->setCity($contactData['city'] ?? '');
        $owner->setState($contactData['state'] ?? '');
        $owner->setPostalCode($contactData['postcode'] ?? '');
        $owner->setCountryCode($contactData['country'] ?? '');
        $owner->setPhone($contactData['phone'] ?? '');

        return $owner;
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
            'mod_ascio_monitoring',
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
                'mod_ascio_monitoring',
                $this->data,
                ['whmcs_service_id' => $this->serviceId]
            );
        } else {
            $this->data['whmcs_service_id'] = $this->serviceId;
            $this->data['user_id'] = $this->userId;
            $this->db->insert('mod_ascio_monitoring', $this->data);
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
            'mod_ascio_monitoring',
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
            'name' => $formData['name'] ?? '',
            'tier' => (int)($formData['tier'] ?? 1),
            'notification_frequency' => $formData['notification_frequency'] ?? 'Daily',
            'email_notification_1' => $formData['email_notification_1'] ?? '',
            'email_notification_2' => $formData['email_notification_2'] ?? '',
            'email_notification_3' => $formData['email_notification_3'] ?? '',
            'email_notification_4' => $formData['email_notification_4'] ?? '',
            'email_notification_5' => $formData['email_notification_5'] ?? '',
            'period' => (int)($formData['period'] ?? 1),
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
     * Get monitoring info from API.
     *
     * @param string $handle NameWatch handle
     * @return object NameWatchInfo
     */
    public function getInfo(string $handle)
    {
        $response = $this->client->getNameWatch($handle);
        $result = $this->responseHandler->handleResponse($response, 'GetNameWatch');
        return $result->getNameWatchInfo();
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
