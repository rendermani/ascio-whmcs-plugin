<?php

namespace Ascio\Core;

/**
 * Object type constants - identifies which product type for queue polling.
 */
final class ObjectType
{
    public const SSL_CERTIFICATE = 'SslCertificateType';
    public const NAME_WATCH = 'NameWatchType';       // Domain Monitoring
    public const DEFENSIVE = 'DefensiveType';        // Defensive/DPML
    public const MARK = 'MarkType';                  // TMCH
    public const AUTO_INSTALL_SSL = 'AutoInstallSslType';
    public const DOMAIN = 'DomainType';

    /**
     * Get all product object types (excluding Domain).
     *
     * @return array
     */
    public static function productTypes(): array
    {
        return [
            self::SSL_CERTIFICATE,
            self::NAME_WATCH,
            self::DEFENSIVE,
            self::MARK,
        ];
    }

    /**
     * Get human-readable name for object type.
     *
     * @param string $type
     * @return string
     */
    public static function getDisplayName(string $type): string
    {
        return match ($type) {
            self::SSL_CERTIFICATE => 'SSL Certificate',
            self::NAME_WATCH => 'Domain Monitoring',
            self::DEFENSIVE => 'Defensive Registration',
            self::MARK => 'TMCH',
            self::AUTO_INSTALL_SSL => 'Auto Install SSL',
            self::DOMAIN => 'Domain',
            default => $type,
        };
    }
}
