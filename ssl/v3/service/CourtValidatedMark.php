<?php

namespace ascio\v3;

class CourtValidatedMark extends AbstractMark
{

    /**
     * @var string $CourtName
     */
    protected $CourtName = null;

    /**
     * @var string $ReferenceNumber
     */
    protected $ReferenceNumber = null;

    /**
     * @var string $Country
     */
    protected $Country = null;

    /**
     * @var string $Region
     */
    protected $Region = null;

    /**
     * @var \DateTime $ProtectionDate
     */
    protected $ProtectionDate = null;

    
    public function __construct()
    {
      parent::__construct();
    }

    /**
     * @return string
     */
    public function getCourtName()
    {
      return $this->CourtName;
    }

    /**
     * @param string $CourtName
     * @return \ascio\v3\CourtValidatedMark
     */
    public function setCourtName($CourtName)
    {
      $this->CourtName = $CourtName;
      return $this;
    }

    /**
     * @return string
     */
    public function getReferenceNumber()
    {
      return $this->ReferenceNumber;
    }

    /**
     * @param string $ReferenceNumber
     * @return \ascio\v3\CourtValidatedMark
     */
    public function setReferenceNumber($ReferenceNumber)
    {
      $this->ReferenceNumber = $ReferenceNumber;
      return $this;
    }

    /**
     * @return string
     */
    public function getCountry()
    {
      return $this->Country;
    }

    /**
     * @param string $Country
     * @return \ascio\v3\CourtValidatedMark
     */
    public function setCountry($Country)
    {
      $this->Country = $Country;
      return $this;
    }

    /**
     * @return string
     */
    public function getRegion()
    {
      return $this->Region;
    }

    /**
     * @param string $Region
     * @return \ascio\v3\CourtValidatedMark
     */
    public function setRegion($Region)
    {
      $this->Region = $Region;
      return $this;
    }

    /**
     * @return \DateTime
     */
    public function getProtectionDate()
    {
      if ($this->ProtectionDate == null) {
        return null;
      } else {
        try {
          return new \DateTime($this->ProtectionDate);
        } catch (\Exception $e) {
          return false;
        }
      }
    }

    /**
     * @param \DateTime $ProtectionDate
     * @return \ascio\v3\CourtValidatedMark
     */
    public function setProtectionDate(\DateTime $ProtectionDate)
    {
      $this->ProtectionDate = $ProtectionDate->format(\DateTime::ATOM);
      return $this;
    }

}
