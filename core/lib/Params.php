<?php

namespace Ascio\Core;

use Ascio\Core\Contracts\ParamsInterface;
use Ascio\Core\Contracts\DatabaseInterface;

/**
 * Shared credential and parameter manager for all Ascio products.
 *
 * Loads credentials from mod_ascio_settings table (preferred) with fallback
 * to mod_asciossl_settings for backward compatibility.
 *
 * Priority: mod_ascio_settings > mod_asciossl_settings
 */
class Params implements ParamsInterface
{
    /** @var string Live account username */
    protected string $account = '';

    /** @var string Live account password */
    protected string $password = '';

    /** @var string Test account username */
    protected string $testAccount = '';

    /** @var string Test account password */
    protected string $testPassword = '';

    /** @var bool Whether running in test mode */
    protected bool $testmode = true;

    /** @var int|null WHMCS service ID */
    protected ?int $serviceId = null;

    /** @var int|null WHMCS user ID */
    protected ?int $userId = null;

    /** @var object|null Additional settings */
    protected ?object $settings = null;

    /** @var DatabaseInterface|null Database adapter */
    protected ?DatabaseInterface $db;

    /**
     * @param array|null $whmcsParams WHMCS module parameters
     * @param DatabaseInterface|null $db Optional database adapter for testing
     */
    public function __construct(?array $whmcsParams = null, ?DatabaseInterface $db = null)
    {
        $this->db = $db;
        $this->loadSettings();

        if ($whmcsParams !== null) {
            $this->serviceId = $whmcsParams['serviceid'] ?? null;
            $this->userId = $whmcsParams['userid'] ?? null;
        }
    }

    /**
     * Load settings from database.
     */
    protected function loadSettings(): void
    {
        if ($this->db !== null) {
            $settings = $this->loadFromDb();
        } else {
            $settings = $this->loadFromCapsule();
        }

        $this->account = $settings['Account'] ?? '';
        $this->password = $settings['Password'] ?? '';
        $this->testAccount = $settings['AccountTesting'] ?? '';
        $this->testPassword = $settings['PasswordTesting'] ?? '';
        $this->testmode = ($settings['Environment'] ?? 'testing') === 'testing';
        $this->settings = (object) $settings;
    }

    /**
     * Load settings using injected database adapter.
     *
     * @return array
     */
    protected function loadFromDb(): array
    {
        // Try new shared table first
        $rows = $this->db->select('mod_ascio_settings', ['name', 'value'], []);
        if (!empty($rows)) {
            $settings = [];
            foreach ($rows as $row) {
                $settings[$row->name] = $row->value;
            }
            return $settings;
        }

        // Fallback to SSL settings table
        $rows = $this->db->select('mod_asciossl_settings', ['name', 'value'], []);
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row->name] = $row->value;
        }
        return $settings;
    }

    /**
     * Load settings using WHMCS Capsule.
     *
     * @return array
     */
    protected function loadFromCapsule(): array
    {
        // Only load Capsule if available (not during unit tests)
        if (!class_exists('\Illuminate\Database\Capsule\Manager')) {
            return [];
        }

        $capsule = \Illuminate\Database\Capsule\Manager::class;

        // Try new shared table first (mod_ascio_settings)
        try {
            if ($capsule::schema()->hasTable('mod_ascio_settings')) {
                $table = $capsule::table('mod_ascio_settings');
                $rows = $table->get();
                if ($rows->isNotEmpty()) {
                    $settings = [];
                    foreach ($rows as $row) {
                        $settings[$row->name] = $row->value;
                    }
                    return $settings;
                }
            }
        } catch (\Exception $e) {
            // Table doesn't exist, try fallback
        }

        // Fallback to SSL settings table (mod_asciossl_settings)
        try {
            if ($capsule::schema()->hasTable('mod_asciossl_settings')) {
                $table = $capsule::table('mod_asciossl_settings');
                $settings = [];
                foreach ($table->get() as $row) {
                    $settings[$row->name] = $row->value;
                }
                return $settings;
            }
        } catch (\Exception $e) {
            // Neither table exists
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getCredentials(bool $forceLive = false): array
    {
        if ($forceLive || !$this->testmode) {
            return [
                'Account' => $this->account,
                'Password' => $this->password,
            ];
        }

        return [
            'Account' => $this->testAccount,
            'Password' => $this->testPassword,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getWsdlV3(bool $forceLive = false): string
    {
        if ($forceLive || !$this->testmode) {
            return 'https://aws.ascio.com/v3/aws.wsdl';
        }
        return 'https://aws.demo.ascio.com/v3/aws.wsdl';
    }

    /**
     * Get WSDL URL for v2 API (legacy).
     *
     * @param bool $forceLive
     * @return string
     */
    public function getWsdlV2(bool $forceLive = false): string
    {
        $prefix = ($forceLive || !$this->testmode) ? '' : 'demo.';
        return "https://aws.{$prefix}ascio.com/2012/01/01/AscioService.wsdl";
    }

    /**
     * {@inheritdoc}
     */
    public function isTestMode(): bool
    {
        return $this->testmode;
    }

    /**
     * {@inheritdoc}
     */
    public function getServiceId(): ?int
    {
        return $this->serviceId;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserId(): ?int
    {
        return $this->userId;
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
     * Set user ID.
     *
     * @param int $userId
     * @return self
     */
    public function setUserId(int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * Get additional settings object.
     *
     * @return object|null
     */
    public function getSettings(): ?object
    {
        return $this->settings;
    }

    /**
     * Get a specific setting value.
     *
     * @param string $name Setting name
     * @param mixed $default Default value if not found
     * @return mixed
     */
    public function getSetting(string $name, $default = null)
    {
        return $this->settings->$name ?? $default;
    }

    /**
     * Create instance with explicit credentials (for testing).
     *
     * @param string $account
     * @param string $password
     * @param bool $testmode
     * @return self
     */
    public static function withCredentials(string $account, string $password, bool $testmode = true): self
    {
        $instance = new self();
        if ($testmode) {
            $instance->testAccount = $account;
            $instance->testPassword = $password;
        } else {
            $instance->account = $account;
            $instance->password = $password;
        }
        $instance->testmode = $testmode;
        return $instance;
    }
}
