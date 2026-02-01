<?php

namespace Ascio\Tests\Mocks;

/**
 * Mock for WHMCS\Carbon (extends DateTime)
 */
class CarbonMock extends \DateTime
{
    public static function createFromFormat(string $format, string $datetime, ?\DateTimeZone $timezone = null): self|false
    {
        $dt = \DateTime::createFromFormat($format, $datetime, $timezone);
        if ($dt === false) {
            return false;
        }
        $carbon = new self();
        $carbon->setTimestamp($dt->getTimestamp());
        return $carbon;
    }

    public function addDays(int $days): self
    {
        $this->modify("+{$days} days");
        return $this;
    }
}

/**
 * Mock for WHMCS\Domain\Registrar\Domain
 */
class DomainMock
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_DELETED = 'deleted';

    private string $domain = '';
    private array $nameservers = [];
    private string $registrationStatus = '';
    private ?\DateTime $expiryDate = null;
    private bool $restorable = false;
    private bool $idProtectionStatus = false;
    private bool $dnsManagementStatus = false;
    private bool $emailForwardingStatus = false;
    private bool $isIrtpEnabled = false;
    private bool $irtpOptOutStatus = false;
    private bool $irtpTransferLock = false;
    private ?\DateTime $irtpTransferLockExpiryDate = null;
    private string $registrantEmailAddress = '';
    private array $irtpVerificationTriggerFields = [];

    public function setDomain(string $domain): self
    {
        $this->domain = $domain;
        return $this;
    }

    public function setNameservers(array $nameservers): self
    {
        $this->nameservers = $nameservers;
        return $this;
    }

    public function setRegistrationStatus(string $status): self
    {
        $this->registrationStatus = $status;
        return $this;
    }

    public function setExpiryDate(\DateTime $date): self
    {
        $this->expiryDate = $date;
        return $this;
    }

    public function setRestorable(bool $restorable): self
    {
        $this->restorable = $restorable;
        return $this;
    }

    public function setIdProtectionStatus(bool $status): self
    {
        $this->idProtectionStatus = $status;
        return $this;
    }

    public function setDnsManagementStatus(bool $status): self
    {
        $this->dnsManagementStatus = $status;
        return $this;
    }

    public function setEmailForwardingStatus(bool $status): self
    {
        $this->emailForwardingStatus = $status;
        return $this;
    }

    public function setIsIrtpEnabled(bool $enabled): self
    {
        $this->isIrtpEnabled = $enabled;
        return $this;
    }

    public function setIrtpOptOutStatus(bool $status): self
    {
        $this->irtpOptOutStatus = $status;
        return $this;
    }

    public function setIrtpTransferLock(bool $lock): self
    {
        $this->irtpTransferLock = $lock;
        return $this;
    }

    public function setIrtpTransferLockExpiryDate(\DateTime $date): self
    {
        $this->irtpTransferLockExpiryDate = $date;
        return $this;
    }

    public function setRegistrantEmailAddress(string $email): self
    {
        $this->registrantEmailAddress = $email;
        return $this;
    }

    public function setIrtpVerificationTriggerFields(array $fields): self
    {
        $this->irtpVerificationTriggerFields = $fields;
        return $this;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function toArray(): array
    {
        return [
            'domain' => $this->domain,
            'nameservers' => $this->nameservers,
            'registrationStatus' => $this->registrationStatus,
            'expiryDate' => $this->expiryDate?->format('Y-m-d'),
            'restorable' => $this->restorable,
            'idProtectionStatus' => $this->idProtectionStatus,
            'dnsManagementStatus' => $this->dnsManagementStatus,
            'emailForwardingStatus' => $this->emailForwardingStatus,
            'isIrtpEnabled' => $this->isIrtpEnabled,
            'irtpOptOutStatus' => $this->irtpOptOutStatus,
            'irtpTransferLock' => $this->irtpTransferLock,
            'registrantEmailAddress' => $this->registrantEmailAddress,
        ];
    }
}

/**
 * Mock for WHMCS\Domains\DomainLookup\ResultsList
 */
class ResultsListMock extends \ArrayObject
{
}

/**
 * Mock for WHMCS\Domains\DomainLookup\SearchResult
 */
class SearchResultMock
{
    public const STATUS_NOT_REGISTERED = 'available';
    public const STATUS_REGISTERED = 'registered';
    public const STATUS_RESERVED = 'reserved';
    public const STATUS_TLD_NOT_SUPPORTED = 'tld_not_supported';

    private string $sld;
    private string $tld;
    private string $status = '';
    private bool $isPremium = false;
    private array $premiumCostPricing = [];

    public function __construct(string $sld, string $tld)
    {
        $this->sld = $sld;
        $this->tld = $tld;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function setPremiumDomain(bool $isPremium): self
    {
        $this->isPremium = $isPremium;
        return $this;
    }

    public function setPremiumCostPricing(array $pricing): self
    {
        $this->premiumCostPricing = $pricing;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getDomainName(): string
    {
        return $this->sld . '.' . $this->tld;
    }
}

/**
 * Mock for WHMCS\Domain\TopLevel\ImportItem
 */
class ImportItemMock
{
    private string $extension = '';
    private int $minYears = 1;
    private int $maxYears = 10;
    private ?float $registerPrice = null;
    private ?float $renewPrice = null;
    private ?float $transferPrice = null;
    private ?float $redemptionFeePrice = null;
    private int $redemptionFeeDays = 30;
    private string $currency = 'USD';
    private bool $eppRequired = true;

    public function setExtension(string $extension): self
    {
        $this->extension = $extension;
        return $this;
    }

    public function setMinYears(int $years): self
    {
        $this->minYears = $years;
        return $this;
    }

    public function setMaxYears(int $years): self
    {
        $this->maxYears = $years;
        return $this;
    }

    public function setRegisterPrice(?float $price): self
    {
        $this->registerPrice = $price;
        return $this;
    }

    public function setRenewPrice(?float $price): self
    {
        $this->renewPrice = $price;
        return $this;
    }

    public function setTransferPrice(?float $price): self
    {
        $this->transferPrice = $price;
        return $this;
    }

    public function setRedemptionFeePrice(?float $price): self
    {
        $this->redemptionFeePrice = $price;
        return $this;
    }

    public function setRedemptionFeeDays(int $days): self
    {
        $this->redemptionFeeDays = $days;
        return $this;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;
        return $this;
    }

    public function setEppRequired(bool $required): self
    {
        $this->eppRequired = $required;
        return $this;
    }
}

/**
 * Mock for WHMCS\Results\ResultsList (for pricing)
 */
class PriceResultsListMock extends \ArrayObject
{
}
