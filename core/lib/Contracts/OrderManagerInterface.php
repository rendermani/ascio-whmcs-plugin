<?php

namespace Ascio\Core\Contracts;

/**
 * Interface for order management operations.
 */
interface OrderManagerInterface
{
    /**
     * Submit an order to Ascio.
     *
     * @param string $orderType Order type (Register, Renew, Delete, etc.)
     * @param object $orderRequest The order request object
     * @param array $context WHMCS context (serviceId, userId, module name)
     * @return string The order ID (prefixed with TEST or A)
     * @throws \Ascio\Core\AscioApiException On API error
     */
    public function submit(string $orderType, $orderRequest, array $context): string;

    /**
     * Validate an order without submitting.
     *
     * @param string $orderType Order type
     * @param object $orderRequest The order request object
     * @return array Validation result with any errors
     */
    public function validate(string $orderType, $orderRequest): array;

    /**
     * Get an existing order.
     *
     * @param string $orderId The order ID
     * @return object Order info
     */
    public function getOrder(string $orderId);
}
