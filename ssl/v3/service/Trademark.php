<?php

namespace ascio\v3;

class Trademark extends AbstractMark
{

    /**
     * @var string $ApplicationId
     */
    protected $ApplicationId = null;

    /**
     * @var string $RegistrationNumber
     */
    protected $RegistrationNumber = null;

    /**
     * @var \DateTime $ApplicationDate
     */
    protected $ApplicationDate = null;

    /**
     * @var \DateTime $RegistrationDate
     */
    protected $RegistrationDate = null;

    /**
     * @var \DateTime $ExpirationDate
     */
    protected $ExpirationDate = null;

    /**
     * @var ArrayOfint $GoodsAndServicesClasses
     */
    protected $GoodsAndServicesClasses = null;

    /**
     * @var string $Jurisdiction
     */
    protected $Jurisdiction = null;

    
    public function __construct()
    {
      parent::__construct();
    }

    /**
     * @return string
     */
    public function getApplicationId()
    {
      return $this->ApplicationId;
    }

    /**
     * @param string $ApplicationId
     * @return \ascio\v3\Trademark
     */
    public function setApplicationId($ApplicationId)
    {
      $this->ApplicationId = $ApplicationId;
      return $this;
    }

    /**
     * @return string
     */
    public function getRegistrationNumber()
    {
      return $this->RegistrationNumber;
    }

    /**
     * @param string $RegistrationNumber
     * @return \ascio\v3\Trademark
     */
    public function setRegistrationNumber($RegistrationNumber)
    {
      $this->RegistrationNumber = $RegistrationNumber;
      return $this;
    }

    /**
     * @return \DateTime
     */
    public function getApplicationDate()
    {
      if ($this->ApplicationDate == null) {
        return null;
      } else {
        try {
          return new \DateTime($this->ApplicationDate);
        } catch (\Exception $e) {
          return false;
        }
      }
    }

    /**
     * @param \DateTime $ApplicationDate
     * @return \ascio\v3\Trademark
     */
    public function setApplicationDate(\DateTime $ApplicationDate)
    {
      $this->ApplicationDate = $ApplicationDate->format(\DateTime::ATOM);
      return $this;
    }

    /**
     * @return \DateTime
     */
    public function getRegistrationDate()
    {
      if ($this->RegistrationDate == null) {
        return null;
      } else {
        try {
          return new \DateTime($this->RegistrationDate);
        } catch (\Exception $e) {
          return false;
        }
      }
    }

    /**
     * @param \DateTime $RegistrationDate
     * @return \ascio\v3\Trademark
     */
    public function setRegistrationDate(\DateTime $RegistrationDate)
    {
      $this->RegistrationDate = $RegistrationDate->format(\DateTime::ATOM);
      return $this;
    }

    /**
     * @return \DateTime
     */
    public function getExpirationDate()
    {
      if ($this->ExpirationDate == null) {
        return null;
      } else {
        try {
          return new \DateTime($this->ExpirationDate);
        } catch (\Exception $e) {
          return false;
        }
      }
    }

    /**
     * @param \DateTime $ExpirationDate
     * @return \ascio\v3\Trademark
     */
    public function setExpirationDate(\DateTime $ExpirationDate)
    {
      $this->ExpirationDate = $ExpirationDate->format(\DateTime::ATOM);
      return $this;
    }

    /**
     * @return ArrayOfint
     */
    public function getGoodsAndServicesClasses()
    {
      return $this->GoodsAndServicesClasses;
    }

    /**
     * @param ArrayOfint $GoodsAndServicesClasses
     * @return \ascio\v3\Trademark
     */
    public function setGoodsAndServicesClasses($GoodsAndServicesClasses)
    {
      $this->GoodsAndServicesClasses = $GoodsAndServicesClasses;
      return $this;
    }

    /**
     * @return string
     */
    public function getJurisdiction()
    {
      return $this->Jurisdiction;
    }

    /**
     * @param string $Jurisdiction
     * @return \ascio\v3\Trademark
     */
    public function setJurisdiction($Jurisdiction)
    {
      $this->Jurisdiction = $Jurisdiction;
      return $this;
    }

}
