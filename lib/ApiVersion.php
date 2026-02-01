<?php
namespace ascio;

/**
 * ApiVersion - Helper class to determine which Ascio API version to use
 *
 * Supports both v2 and v3 APIs with automatic fallback capabilities.
 *
 * Configuration priority:
 * 1. Environment variable ASCIO_USE_V3 (true/1/yes)
 * 2. Registrar config setting ApiVersion ('v3')
 * 3. Default: v2 for backward compatibility
 */
class ApiVersion {

    const VERSION_V2 = 'v2';
    const VERSION_V3 = 'v3';

    /**
     * Determine which API version to use based on configuration
     *
     * @param array|null $config Optional registrar configuration array
     * @return string 'v2' or 'v3'
     */
    public static function getVersion($config = null) {
        // Check environment variable first (highest priority)
        $envValue = getenv('ASCIO_USE_V3');
        if ($envValue !== false) {
            $envLower = strtolower($envValue);
            if ($envLower === 'true' || $envLower === '1' || $envLower === 'yes' || $envLower === 'on') {
                self::log("Using Ascio v3 API (configured via environment variable ASCIO_USE_V3)");
                return self::VERSION_V3;
            }
            // Explicitly set to false/0/no means v2
            if ($envLower === 'false' || $envLower === '0' || $envLower === 'no' || $envLower === 'off') {
                self::log("Using Ascio v2 API (configured via environment variable ASCIO_USE_V3=false)");
                return self::VERSION_V2;
            }
        }

        // Check registrar config (second priority)
        if ($config !== null && isset($config['ApiVersion'])) {
            if (strtolower($config['ApiVersion']) === 'v3') {
                self::log("Using Ascio v3 API (configured via registrar config ApiVersion)");
                return self::VERSION_V3;
            }
        }

        // Default to v2 for backward compatibility
        self::log("Using Ascio v2 API (default)");
        return self::VERSION_V2;
    }

    /**
     * Check if v3 API should be used
     *
     * @param array|null $config Optional registrar configuration array
     * @return bool True if v3 should be used
     */
    public static function useV3($config = null) {
        return self::getVersion($config) === self::VERSION_V3;
    }

    /**
     * Check if v2 API should be used
     *
     * @param array|null $config Optional registrar configuration array
     * @return bool True if v2 should be used
     */
    public static function useV2($config = null) {
        return self::getVersion($config) === self::VERSION_V2;
    }

    /**
     * Log API version information for debugging
     *
     * @param string $message Message to log
     * @return void
     */
    private static function log($message) {
        // Log to syslog for debugging
        syslog(LOG_INFO, "Ascio: " . $message);

        // Also log to WHMCS activity log if available
        if (function_exists('logActivity')) {
            logActivity("Ascio: " . $message);
        }
    }

    /**
     * Create the appropriate Request object based on API version
     *
     * @param array $config Registrar configuration array
     * @return \ascio\v2\domains\Request|\ascio\v3\domains\Request
     */
    public static function createRequest($config) {
        if (self::useV3($config)) {
            require_once(__DIR__ . "/RequestV3.php");
            return new \ascio\v3\domains\Request($config);
        }

        require_once(__DIR__ . "/Request.php");
        return new \ascio\v2\domains\Request($config);
    }
}
?>
