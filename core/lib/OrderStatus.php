<?php

namespace Ascio\Core;

/**
 * Order status constants - same for all Ascio products.
 */
final class OrderStatus
{
    public const PENDING = 'Pending';
    public const PENDING_END_USER_ACTION = 'Pending_End_User_Action';
    public const COMPLETED = 'Completed';
    public const FAILED = 'Failed';
    public const INVALID = 'Invalid';
    public const NOT_VALIDATED = 'Order not validated';

    /**
     * Check if status indicates completion (success or failure).
     *
     * @param string $status
     * @return bool
     */
    public static function isTerminal(string $status): bool
    {
        return in_array($status, [
            self::COMPLETED,
            self::FAILED,
            self::INVALID,
        ], true);
    }

    /**
     * Check if status indicates success.
     *
     * @param string $status
     * @return bool
     */
    public static function isSuccess(string $status): bool
    {
        return $status === self::COMPLETED;
    }

    /**
     * Check if status indicates failure.
     *
     * @param string $status
     * @return bool
     */
    public static function isFailure(string $status): bool
    {
        return in_array($status, [self::FAILED, self::INVALID], true);
    }

    /**
     * Check if status requires order data fetch.
     *
     * @param string $status
     * @return bool
     */
    public static function requiresOrderFetch(string $status): bool
    {
        return in_array($status, [
            self::FAILED,
            self::INVALID,
            self::COMPLETED,
            self::NOT_VALIDATED,
            self::PENDING_END_USER_ACTION,
        ], true);
    }

    /**
     * Map Ascio status to WHMCS service status.
     *
     * @param string $status
     * @return string WHMCS status
     */
    public static function toWhmcsStatus(string $status): string
    {
        return match ($status) {
            self::COMPLETED => 'Active',
            default => 'Pending',
        };
    }
}
