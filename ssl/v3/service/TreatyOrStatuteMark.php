<?php

namespace ascio\v3;

class TreatyOrStatuteMark extends AbstractMark
{

    /**
     * @var string $Title
     */
    protected $Title = null;

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

    /**
     * @var \DateTime $ExecutionDate
     */
    protected $ExecutionDate = null;

    
    public function __construct()
    {
      parent::__construct();
    }

    /**
     * @return string
     */
    public function getTitle()
    {
      return $this->Title;
    }

    /**
     * @param string $Title
     * @return \ascio\v3\TreatyOrStatuteMark
     */
    public function setTitle($Title)
    {
      $this->Title = $Title;
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
     * @return \ascio\v3\TreatyOrStatuteMark
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
     * @return \ascio\v3\TreatyOrStatuteMark
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
     * @return \ascio\v3\TreatyOrStatuteMark
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
     * @return \ascio\v3\TreatyOrStatuteMark
     */
    public function setProtectionDate(\DateTime $ProtectionDate)
    {
      $this->ProtectionDate = $ProtectionDate->format(\DateTime::ATOM);
      return $this;
    }

    /**
     * @return \DateTime
     */
    public function getExecutionDate()
    {
      if ($this->ExecutionDate == null) {
        return null;
      } else {
        try {
          return new \DateTime($this->ExecutionDate);
        } catch (\Exception $e) {
          return false;
        }
      }
    }

    /**
     * @param \DateTime $ExecutionDate
     * @return \ascio\v3\TreatyOrStatuteMark
     */
    public function setExecutionDate(\DateTime $ExecutionDate)
    {
      $this->ExecutionDate = $ExecutionDate->format(\DateTime::ATOM);
      return $this;
    }

}
