<?php

namespace Ascio\Core;

/**
 * Order type constants - shared across all Ascio products.
 */
final class OrderType
{
    public const REGISTER = 'Register';
    public const RENEW = 'Renew';
    public const DELETE = 'Delete';
    public const TRANSFER = 'Transfer';
    public const DETAILS_UPDATE = 'DetailsUpdate';  // Reissue
    public const RESTORE = 'Restore';
    public const OWNER_CHANGE = 'OwnerChange';
    public const CONTACT_UPDATE = 'ContactUpdate';

    /**
     * Get all valid order types.
     *
     * @return array
     */
    public static function all(): array
    {
        return [
            self::REGISTER,
            self::RENEW,
            self::DELETE,
            self::TRANSFER,
            self::DETAILS_UPDATE,
            self::RESTORE,
            self::OWNER_CHANGE,
            self::CONTACT_UPDATE,
        ];
    }

    /**
     * Check if a type is valid.
     *
     * @param string $type
     * @return bool
     */
    public static function isValid(string $type): bool
    {
        return in_array($type, self::all(), true);
    }
}
