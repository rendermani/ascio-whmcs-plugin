<?php

namespace Ascio\Core\Contracts;

/**
 * Interface for Ascio v3 SOAP API client operations.
 * Enables dependency injection and mocking for tests.
 */
interface AscioClientInterface
{
    /**
     * Create an order in the Ascio system.
     *
     * @param object $orderRequest The order request object
     * @return object CreateOrderResponse
     */
    public function createOrder($orderRequest);

    /**
     * Validate an order without submitting it.
     *
     * @param object $orderRequest The order request object
     * @return object ValidateOrderResponse
     */
    public function validateOrder($orderRequest);

    /**
     * Get order information by order ID.
     *
     * @param string $orderId The order ID
     * @return object GetOrderResponse
     */
    public function getOrder(string $orderId);

    /**
     * Poll the message queue for status updates.
     *
     * @param string $objectType Object type (SslCertificateType, NameWatchType, etc.)
     * @param string $messageType Message type (MessageToPartner)
     * @return object PollQueueResponse
     */
    public function pollQueue(string $objectType, string $messageType);

    /**
     * Get a specific queue message.
     *
     * @param string $messageId The message ID
     * @return object GetQueueMessageResponse
     */
    public function getQueueMessage(string $messageId);

    /**
     * Acknowledge a queue message.
     *
     * @param string $messageId The message ID
     * @return void
     */
    public function ackQueueMessage(string $messageId): void;

    /**
     * Get SSL certificate information.
     *
     * @param string $handle The certificate handle
     * @return object GetSslCertificateResponse
     */
    public function getSslCertificate(string $handle);

    /**
     * Get NameWatch (monitoring) information.
     *
     * @param string $handle The NameWatch handle
     * @return object GetNameWatchResponse
     */
    public function getNameWatch(string $handle);

    /**
     * Get Defensive registration information.
     *
     * @param string $handle The Defensive handle
     * @return object GetDefensiveResponse
     */
    public function getDefensive(string $handle);

    /**
     * Get Mark (TMCH) information.
     *
     * @param string $handle The Mark handle
     * @return object GetMarkResponse
     */
    public function getMark(string $handle);

    /**
     * Upload documentation for a Mark/TMCH order.
     *
     * @param object $uploadRequest The upload request
     * @return object UploadDocumentationResponse
     */
    public function uploadDocumentation($uploadRequest);
}
