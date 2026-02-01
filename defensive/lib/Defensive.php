<?php

namespace Ascio\Defensive;

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
 * Defensive Registration (DPML) business logic.
 * Handles registration, renewal, and termination of defensive domain registrations.
 */
class Defensive
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
        $this->responseHandler = new ResponseHandler('asciodefensive');
        $this->serviceId = $params->getServiceId();
        $this->userId = $params->getUserId();
    }

    /**
     * Register a new defensive domain.
     *
     * @param array $contactData Contact information
     * @return array Result with order_id, status, etc.
     */
    public function register(array $contactData): array
    {
        return $this->submit($contactData, OrderType::REGISTER);
    }

    /**
     * Renew an existing defensive registration.
     *
     * @param array $contactData Contact information
     * @return array Result
     */
    public function renew(array $contactData): array
    {
        return $this->submit($contactData, OrderType::RENEW);
    }

    /**
     * Terminate/delete a defensive registration.
     *
     * @return array Result
     */
    public function terminate(): array
    {
        return $this->submit([], OrderType::DELETE);
    }

    /**
     * Submit a defensive order to Ascio.
     *
     * @param array $contactData Contact information
     * @param string $orderType Order type
     * @return array Result
     */
    protected function submit(array $contactData, string $orderType): array
    {
        $data = $this->readDb();

        // Build contacts
        $owner = $this->buildRegistrant($contactData, 'owner');
        $admin = $this->buildContact($contactData, 'admin');
        $tech = $this->buildContact($contactData, 'tech');

        // Build Defensive object
        $defensive = new v3\Defensive();
        $defensive->setName($data->name);
        $defensive->setOwner($owner);
        $defensive->setAdmin($admin);
        $defensive->setTech($tech);

        // Set mark handle if provided
        if (!empty($data->mark_handle)) {
            $defensive->setMarkHandle($data->mark_handle);
        }

        // For renewal/delete, set handle
        if ($orderType !== OrderType::REGISTER && !empty($data->handle)) {
            $defensive->setHandle($data->handle);
        }

        // Build order request
        $orderRequest = new v3\DefensiveOrderRequest($orderType);
        $orderRequest->setType($orderType);
        $orderRequest->setPeriod($data->period ?? 1);
        $orderRequest->setTransactionComment('WHMCS Defensive Module');
        $orderRequest->setDefensive($defensive);

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
     * Build Registrant object for owner.
     *
     * @param array $contactData
     * @param string $prefix
     * @return v3\Registrant
     */
    protected function buildRegistrant(array $contactData, string $prefix): v3\Registrant
    {
        $registrant = new v3\Registrant();

        $registrant->setName($contactData["{$prefix}_name"] ?? $contactData['name'] ?? '');
        $registrant->setEmail($contactData["{$prefix}_email"] ?? $contactData['email'] ?? '');
        $registrant->setOrgName($contactData["{$prefix}_company"] ?? $contactData['company'] ?? '');
        $registrant->setAddress1($contactData["{$prefix}_address1"] ?? $contactData['address1'] ?? '');
        $registrant->setAddress2($contactData["{$prefix}_address2"] ?? $contactData['address2'] ?? '');
        $registrant->setCity($contactData["{$prefix}_city"] ?? $contactData['city'] ?? '');
        $registrant->setState($contactData["{$prefix}_state"] ?? $contactData['state'] ?? '');
        $registrant->setPostalCode($contactData["{$prefix}_postcode"] ?? $contactData['postcode'] ?? '');
        $registrant->setCountryCode($contactData["{$prefix}_country"] ?? $contactData['country'] ?? '');
        $registrant->setPhone($contactData["{$prefix}_phone"] ?? $contactData['phone'] ?? '');

        return $registrant;
    }

    /**
     * Build Contact object.
     *
     * @param array $contactData
     * @param string $prefix
     * @return v3\Contact
     */
    protected function buildContact(array $contactData, string $prefix): v3\Contact
    {
        $contact = new v3\Contact();

        // Use prefixed data if available, fall back to owner data
        $name = $contactData["{$prefix}_name"] ?? $contactData['owner_name'] ?? $contactData['name'] ?? '';
        $email = $contactData["{$prefix}_email"] ?? $contactData['owner_email'] ?? $contactData['email'] ?? '';
        $company = $contactData["{$prefix}_company"] ?? $contactData['owner_company'] ?? $contactData['company'] ?? '';
        $phone = $contactData["{$prefix}_phone"] ?? $contactData['owner_phone'] ?? $contactData['phone'] ?? '';

        $contact->setName($name);
        $contact->setEmail($email);
        $contact->setOrgName($company);
        $contact->setPhone($phone);

        // Address fields fall back to owner
        $contact->setAddress1($contactData['owner_address1'] ?? $contactData['address1'] ?? '');
        $contact->setAddress2($contactData['owner_address2'] ?? $contactData['address2'] ?? '');
        $contact->setCity($contactData['owner_city'] ?? $contactData['city'] ?? '');
        $contact->setState($contactData['owner_state'] ?? $contactData['state'] ?? '');
        $contact->setPostalCode($contactData['owner_postcode'] ?? $contactData['postcode'] ?? '');
        $contact->setCountryCode($contactData['owner_country'] ?? $contactData['country'] ?? '');

        return $contact;
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
            'mod_ascio_defensive',
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
                'mod_ascio_defensive',
                $this->data,
                ['whmcs_service_id' => $this->serviceId]
            );
        } else {
            $this->data['whmcs_service_id'] = $this->serviceId;
            $this->data['user_id'] = $this->userId;
            $this->db->insert('mod_ascio_defensive', $this->data);
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
            'mod_ascio_defensive',
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
            'mark_handle' => $formData['mark_handle'] ?? '',
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
            'admin_name' => $formData['admin_name'] ?? '',
            'admin_email' => $formData['admin_email'] ?? '',
            'admin_company' => $formData['admin_company'] ?? '',
            'admin_phone' => $formData['admin_phone'] ?? '',
            'tech_name' => $formData['tech_name'] ?? '',
            'tech_email' => $formData['tech_email'] ?? '',
            'tech_company' => $formData['tech_company'] ?? '',
            'tech_phone' => $formData['tech_phone'] ?? '',
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
     * Get defensive info from API.
     *
     * @param string $handle Defensive handle
     * @return object DefensiveInfo
     */
    public function getInfo(string $handle)
    {
        $response = $this->client->getDefensive($handle);
        $result = $this->responseHandler->handleResponse($response, 'GetDefensive');
        return $result->getDefensiveInfo();
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
