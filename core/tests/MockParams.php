<?php

namespace Ascio\Core\Tests;

use Ascio\Core\Contracts\ParamsInterface;

/**
 * Mock parameters implementation for unit testing.
 */
class MockParams implements ParamsInterface
{
    protected string $account = 'test_account';
    protected string $password = 'test_password';
    protected bool $testmode = true;
    protected ?int $serviceId = null;
    protected ?int $userId = null;

    /**
     * Create with specific values.
     *
     * @param string $account
     * @param string $password
     * @param bool $testmode
     */
    public function __construct(
        string $account = 'test_account',
        string $password = 'test_password',
        bool $testmode = true
    ) {
        $this->account = $account;
        $this->password = $password;
        $this->testmode = $testmode;
    }

    /**
     * {@inheritdoc}
     */
    public function getCredentials(bool $forceLive = false): array
    {
        return [
            'Account' => $this->account,
            'Password' => $this->password,
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
     * Set test mode.
     *
     * @param bool $testmode
     * @return self
     */
    public function setTestMode(bool $testmode): self
    {
        $this->testmode = $testmode;
        return $this;
    }
}
