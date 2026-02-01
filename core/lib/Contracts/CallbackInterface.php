<?php

namespace Ascio\Core\Contracts;

/**
 * Interface for callback handlers that process async order status updates.
 */
interface CallbackInterface
{
    /**
     * Process a callback message from the Ascio queue.
     *
     * @param string $orderId The order ID
     * @param string $status The order status
     * @param string $messageId The queue message ID
     * @param mixed $message Optional pre-fetched message content
     * @return void
     */
    public function process(string $orderId, string $status, string $messageId, $message = null): void;

    /**
     * Get the database table name for this callback type.
     *
     * @return string Table name
     */
    public function getTableName(): string;

    /**
     * Get the object type identifier for this callback.
     *
     * @return string Object type (SslCertificateType, NameWatchType, etc.)
     */
    public function getObjectType(): string;
}
