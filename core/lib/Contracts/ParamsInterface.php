<?php

namespace Ascio\Core\Contracts;

/**
 * Interface for credential and parameter management.
 * Enables dependency injection for testing with different credentials.
 */
interface ParamsInterface
{
    /**
     * Get API credentials for authentication.
     *
     * @param bool $forceLive Force use of live credentials even in test mode
     * @return array ['Account' => string, 'Password' => string]
     */
    public function getCredentials(bool $forceLive = false): array;

    /**
     * Get the v3 WSDL URL based on environment.
     *
     * @param bool $forceLive Force use of live WSDL
     * @return string WSDL URL
     */
    public function getWsdlV3(bool $forceLive = false): string;

    /**
     * Check if running in test mode.
     *
     * @return bool True if in test mode
     */
    public function isTestMode(): bool;

    /**
     * Get the WHMCS service ID.
     *
     * @return int|null Service ID or null if not set
     */
    public function getServiceId(): ?int;

    /**
     * Get the WHMCS user ID.
     *
     * @return int|null User ID or null if not set
     */
    public function getUserId(): ?int;
}
