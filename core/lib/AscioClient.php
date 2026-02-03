<?php

namespace Ascio\Core;

use Ascio\Core\Contracts\AscioClientInterface;
use Ascio\Core\Contracts\ParamsInterface;
use ascio\v3 as v3;

/**
 * Unified Ascio v3 SOAP API client.
 * Used by all product modules (SSL, Monitoring, Defensive, TMCH).
 */
class AscioClient implements AscioClientInterface
{
    /** @var v3\AscioService SOAP client */
    protected $client;

    /** @var ParamsInterface Parameters/credentials */
    protected ParamsInterface $params;

    /**
     * @param ParamsInterface $params Credential provider
     * @param v3\AscioService|null $client Optional injected client for testing
     */
    public function __construct(ParamsInterface $params, $client = null)
    {
        $this->params = $params;

        if ($client !== null) {
            $this->client = $client;
        } else {
            $this->initClient();
        }
    }

    /**
     * Initialize the SOAP client.
     */
    protected function initClient(): void
    {
        $this->client = new v3\AscioService(
            ['trace' => true],
            $this->params->getWsdlV3()
        );

        $header = new \SoapHeader(
            'http://www.ascio.com/2013/02',
            'SecurityHeaderDetails',
            $this->params->getCredentials(),
            false
        );
        $this->client->__setSoapHeaders($header);
    }

    /**
     * Get the underlying SOAP client.
     *
     * @return v3\AscioService
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * {@inheritdoc}
     */
    public function createOrder($orderRequest)
    {
        return $this->client->CreateOrder(new v3\CreateOrder($orderRequest));
    }

    /**
     * {@inheritdoc}
     */
    public function validateOrder($orderRequest)
    {
        return $this->client->ValidateOrder(new v3\ValidateOrder($orderRequest));
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder(string $orderId)
    {
        $request = new v3\GetOrderRequest();
        $request->setOrderId($orderId);
        return $this->client->GetOrder(new v3\GetOrder($request));
    }

    /**
     * {@inheritdoc}
     */
    public function pollQueue(string $objectType, string $messageType)
    {
        $request = new v3\PollQueueRequest();
        $request->setObjectType($objectType);
        $request->setMessageType($messageType);
        return $this->client->PollQueue(new v3\PollQueue($request));
    }

    /**
     * {@inheritdoc}
     */
    public function getQueueMessage(string $messageId)
    {
        $request = new v3\GetQueueMessageRequest();
        $request->setMessageId($messageId);
        return $this->client->GetQueueMessage(new v3\GetQueueMessage($request));
    }

    /**
     * {@inheritdoc}
     */
    public function ackQueueMessage(string $messageId): void
    {
        $request = new v3\AckQueueMessageRequest();
        $request->setMessageId($messageId);
        $result = $this->client->AckQueueMessage(new v3\AckQueueMessage($request));

        if ($result->AckQueueMessageResult->getResultCode() !== 200) {
            throw new AscioApiException(
                $result->AckQueueMessageResult->getResultMessage() . " ({$messageId})",
                $result->AckQueueMessageResult->getResultCode()
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getSslCertificate(string $handle)
    {
        $request = new v3\GetSslCertificateRequest();
        $request->setHandle($handle);
        return $this->client->GetSslCertificate(new v3\GetSslCertificate($request));
    }

    /**
     * {@inheritdoc}
     */
    public function getNameWatch(string $handle)
    {
        $request = new v3\GetNameWatchRequest();
        $request->setHandle($handle);
        return $this->client->GetNameWatch(new v3\GetNameWatch($request));
    }

    /**
     * {@inheritdoc}
     */
    public function getDefensive(string $handle)
    {
        $request = new v3\GetDefensiveRequest();
        $request->setHandle($handle);
        return $this->client->GetDefensive(new v3\GetDefensive($request));
    }

    /**
     * {@inheritdoc}
     */
    public function getMark(string $handle)
    {
        $request = new v3\GetMarkRequest();
        $request->setHandle($handle);
        return $this->client->GetMark(new v3\GetMark($request));
    }

    /**
     * {@inheritdoc}
     */
    public function uploadDocumentation($uploadRequest)
    {
        return $this->client->UploadDocumentation(new v3\UploadDocumentation($uploadRequest));
    }

    /**
     * Check if the client is in test mode.
     *
     * @return bool
     */
    public function isTestMode(): bool
    {
        return $this->params->isTestMode();
    }

    /**
     * Format order ID with appropriate prefix.
     *
     * @param string|int $orderId Raw order ID from API
     * @return string Prefixed order ID
     */
    public function formatOrderId($orderId): string
    {
        // Already prefixed
        if (str_starts_with((string)$orderId, 'TEST') || str_starts_with((string)$orderId, 'A')) {
            return (string)$orderId;
        }

        return $this->isTestMode() ? "TEST{$orderId}" : "A{$orderId}";
    }

    /**
     * Parse order ID to get raw ID without prefix.
     *
     * @param string $orderId Prefixed order ID
     * @return string Raw order ID
     */
    public function parseOrderId(string $orderId): string
    {
        if (str_starts_with($orderId, 'TEST')) {
            return substr($orderId, 4);
        }
        if (str_starts_with($orderId, 'A')) {
            return substr($orderId, 1);
        }
        return $orderId;
    }
}
