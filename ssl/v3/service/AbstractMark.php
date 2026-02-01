<?php

namespace ascio\v3;

abstract class AbstractMark
{

    /**
     * @var string $Handle
     */
    protected $Handle = null;

    /**
     * @var string $MarkName
     */
    protected $MarkName = null;

    /**
     * @var string $MarkId
     */
    protected $MarkId = null;

    /**
     * @var string $AuthInfo
     */
    protected $AuthInfo = null;

    /**
     * @var MarkServiceType $ServiceType
     */
    protected $ServiceType = null;

    /**
     * @var string $GoodsAndServicesDescription
     */
    protected $GoodsAndServicesDescription = null;

    /**
     * @var ArrayOfstring $Labels
     */
    protected $Labels = null;

    /**
     * @var string $ClaimEmailNotification1
     */
    protected $ClaimEmailNotification1 = null;

    /**
     * @var string $ClaimEmailNotification2
     */
    protected $ClaimEmailNotification2 = null;

    /**
     * @var string $ClaimEmailNotification3
     */
    protected $ClaimEmailNotification3 = null;

    /**
     * @var string $ClaimEmailNotification4
     */
    protected $ClaimEmailNotification4 = null;

    /**
     * @var string $ClaimEmailNotification5
     */
    protected $ClaimEmailNotification5 = null;

    /**
     * @var NotificationFrequencyType $NotificationFrequency
     */
    protected $NotificationFrequency = null;

    /**
     * @var Registrant $Owner
     */
    protected $Owner = null;

    /**
     * @var Contact $Reseller
     */
    protected $Reseller = null;

    /**
     * @var Extensions $Extensions
     */
    protected $Extensions = null;

    /**
     * @var string $ObjectComment
     */
    protected $ObjectComment = null;

    
    public function __construct()
    {
    
    }

    /**
     * @return string
     */
    public function getHandle()
    {
      return $this->Handle;
    }

    /**
     * @param string $Handle
     * @return \ascio\v3\AbstractMark
     */
    public function setHandle($Handle)
    {
      $this->Handle = $Handle;
      return $this;
    }

    /**
     * @return string
     */
    public function getMarkName()
    {
      return $this->MarkName;
    }

    /**
     * @param string $MarkName
     * @return \ascio\v3\AbstractMark
     */
    public function setMarkName($MarkName)
    {
      $this->MarkName = $MarkName;
      return $this;
    }

    /**
     * @return string
     */
    public function getMarkId()
    {
      return $this->MarkId;
    }

    /**
     * @param string $MarkId
     * @return \ascio\v3\AbstractMark
     */
    public function setMarkId($MarkId)
    {
      $this->MarkId = $MarkId;
      return $this;
    }

    /**
     * @return string
     */
    public function getAuthInfo()
    {
      return $this->AuthInfo;
    }

    /**
     * @param string $AuthInfo
     * @return \ascio\v3\AbstractMark
     */
    public function setAuthInfo($AuthInfo)
    {
      $this->AuthInfo = $AuthInfo;
      return $this;
    }

    /**
     * @return MarkServiceType
     */
    public function getServiceType()
    {
      return $this->ServiceType;
    }

    /**
     * @param MarkServiceType $ServiceType
     * @return \ascio\v3\AbstractMark
     */
    public function setServiceType($ServiceType)
    {
      $this->ServiceType = $ServiceType;
      return $this;
    }

    /**
     * @return string
     */
    public function getGoodsAndServicesDescription()
    {
      return $this->GoodsAndServicesDescription;
    }

    /**
     * @param string $GoodsAndServicesDescription
     * @return \ascio\v3\AbstractMark
     */
    public function setGoodsAndServicesDescription($GoodsAndServicesDescription)
    {
      $this->GoodsAndServicesDescription = $GoodsAndServicesDescription;
      return $this;
    }

    /**
     * @return ArrayOfstring
     */
    public function getLabels()
    {
      return $this->Labels;
    }

    /**
     * @param ArrayOfstring $Labels
     * @return \ascio\v3\AbstractMark
     */
    public function setLabels($Labels)
    {
      $this->Labels = $Labels;
      return $this;
    }

    /**
     * @return string
     */
    public function getClaimEmailNotification1()
    {
      return $this->ClaimEmailNotification1;
    }

    /**
     * @param string $ClaimEmailNotification1
     * @return \ascio\v3\AbstractMark
     */
    public function setClaimEmailNotification1($ClaimEmailNotification1)
    {
      $this->ClaimEmailNotification1 = $ClaimEmailNotification1;
      return $this;
    }

    /**
     * @return string
     */
    public function getClaimEmailNotification2()
    {
      return $this->ClaimEmailNotification2;
    }

    /**
     * @param string $ClaimEmailNotification2
     * @return \ascio\v3\AbstractMark
     */
    public function setClaimEmailNotification2($ClaimEmailNotification2)
    {
      $this->ClaimEmailNotification2 = $ClaimEmailNotification2;
      return $this;
    }

    /**
     * @return string
     */
    public function getClaimEmailNotification3()
    {
      return $this->ClaimEmailNotification3;
    }

    /**
     * @param string $ClaimEmailNotification3
     * @return \ascio\v3\AbstractMark
     */
    public function setClaimEmailNotification3($ClaimEmailNotification3)
    {
      $this->ClaimEmailNotification3 = $ClaimEmailNotification3;
      return $this;
    }

    /**
     * @return string
     */
    public function getClaimEmailNotification4()
    {
      return $this->ClaimEmailNotification4;
    }

    /**
     * @param string $ClaimEmailNotification4
     * @return \ascio\v3\AbstractMark
     */
    public function setClaimEmailNotification4($ClaimEmailNotification4)
    {
      $this->ClaimEmailNotification4 = $ClaimEmailNotification4;
      return $this;
    }

    /**
     * @return string
     */
    public function getClaimEmailNotification5()
    {
      return $this->ClaimEmailNotification5;
    }

    /**
     * @param string $ClaimEmailNotification5
     * @return \ascio\v3\AbstractMark
     */
    public function setClaimEmailNotification5($ClaimEmailNotification5)
    {
      $this->ClaimEmailNotification5 = $ClaimEmailNotification5;
      return $this;
    }

    /**
     * @return NotificationFrequencyType
     */
    public function getNotificationFrequency()
    {
      return $this->NotificationFrequency;
    }

    /**
     * @param NotificationFrequencyType $NotificationFrequency
     * @return \ascio\v3\AbstractMark
     */
    public function setNotificationFrequency($NotificationFrequency)
    {
      $this->NotificationFrequency = $NotificationFrequency;
      return $this;
    }

    /**
     * @return Registrant
     */
    public function getOwner()
    {
      return $this->Owner;
    }

    /**
     * @param Registrant $Owner
     * @return \ascio\v3\AbstractMark
     */
    public function setOwner($Owner)
    {
      $this->Owner = $Owner;
      return $this;
    }

    /**
     * @return Contact
     */
    public function getReseller()
    {
      return $this->Reseller;
    }

    /**
     * @param Contact $Reseller
     * @return \ascio\v3\AbstractMark
     */
    public function setReseller($Reseller)
    {
      $this->Reseller = $Reseller;
      return $this;
    }

    /**
     * @return Extensions
     */
    public function getExtensions()
    {
      return $this->Extensions;
    }

    /**
     * @param Extensions $Extensions
     * @return \ascio\v3\AbstractMark
     */
    public function setExtensions($Extensions)
    {
      $this->Extensions = $Extensions;
      return $this;
    }

    /**
     * @return string
     */
    public function getObjectComment()
    {
      return $this->ObjectComment;
    }

    /**
     * @param string $ObjectComment
     * @return \ascio\v3\AbstractMark
     */
    public function setObjectComment($ObjectComment)
    {
      $this->ObjectComment = $ObjectComment;
      return $this;
    }

}
