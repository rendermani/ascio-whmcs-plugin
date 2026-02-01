<?php

namespace ascio\v3;

class NameWatchInfo
{

    /**
     * @var string $Handle
     */
    protected $Handle = null;

    /**
     * @var string $Status
     */
    protected $Status = null;

    /**
     * @var \DateTime $Created
     */
    protected $Created = null;

    /**
     * @var \DateTime $Expires
     */
    protected $Expires = null;

    /**
     * @var string $Name
     */
    protected $Name = null;

    /**
     * @var NotificationFrequencyType $NotificationFrequency
     */
    protected $NotificationFrequency = null;

    /**
     * @var int $Tier
     */
    protected $Tier = null;

    /**
     * @var string $EmailNotification1
     */
    protected $EmailNotification1 = null;

    /**
     * @var string $EmailNotification2
     */
    protected $EmailNotification2 = null;

    /**
     * @var string $EmailNotification3
     */
    protected $EmailNotification3 = null;

    /**
     * @var string $EmailNotification4
     */
    protected $EmailNotification4 = null;

    /**
     * @var string $EmailNotification5
     */
    protected $EmailNotification5 = null;

    /**
     * @var Registrant $Owner
     */
    protected $Owner = null;

    /**
     * @var Contact $Reseller
     */
    protected $Reseller = null;

    /**
     * @var ArrayOfstring $Labels
     */
    protected $Labels = null;

    /**
     * @var string $ObjectComment
     */
    protected $ObjectComment = null;

    /**
     * @param \DateTime $Created
     * @param NotificationFrequencyType $NotificationFrequency
     * @param int $Tier
     */
    public function __construct(\DateTime $Created = null, $NotificationFrequency = null, $Tier = null)
    {
      $this->Created = $Created ? $Created->format(\DateTime::ATOM) : null;
      $this->NotificationFrequency = $NotificationFrequency;
      $this->Tier = $Tier;
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
     * @return \ascio\v3\NameWatchInfo
     */
    public function setHandle($Handle)
    {
      $this->Handle = $Handle;
      return $this;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
      return $this->Status;
    }

    /**
     * @param string $Status
     * @return \ascio\v3\NameWatchInfo
     */
    public function setStatus($Status)
    {
      $this->Status = $Status;
      return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreated()
    {
      if ($this->Created == null) {
        return null;
      } else {
        try {
          return new \DateTime($this->Created);
        } catch (\Exception $e) {
          return false;
        }
      }
    }

    /**
     * @param \DateTime $Created
     * @return \ascio\v3\NameWatchInfo
     */
    public function setCreated(\DateTime $Created)
    {
      $this->Created = $Created->format(\DateTime::ATOM);
      return $this;
    }

    /**
     * @return \DateTime
     */
    public function getExpires()
    {
      if ($this->Expires == null) {
        return null;
      } else {
        try {
          return new \DateTime($this->Expires);
        } catch (\Exception $e) {
          return false;
        }
      }
    }

    /**
     * @param \DateTime $Expires
     * @return \ascio\v3\NameWatchInfo
     */
    public function setExpires(\DateTime $Expires)
    {
      $this->Expires = $Expires->format(\DateTime::ATOM);
      return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
      return $this->Name;
    }

    /**
     * @param string $Name
     * @return \ascio\v3\NameWatchInfo
     */
    public function setName($Name)
    {
      $this->Name = $Name;
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
     * @return \ascio\v3\NameWatchInfo
     */
    public function setNotificationFrequency($NotificationFrequency)
    {
      $this->NotificationFrequency = $NotificationFrequency;
      return $this;
    }

    /**
     * @return int
     */
    public function getTier()
    {
      return $this->Tier;
    }

    /**
     * @param int $Tier
     * @return \ascio\v3\NameWatchInfo
     */
    public function setTier($Tier)
    {
      $this->Tier = $Tier;
      return $this;
    }

    /**
     * @return string
     */
    public function getEmailNotification1()
    {
      return $this->EmailNotification1;
    }

    /**
     * @param string $EmailNotification1
     * @return \ascio\v3\NameWatchInfo
     */
    public function setEmailNotification1($EmailNotification1)
    {
      $this->EmailNotification1 = $EmailNotification1;
      return $this;
    }

    /**
     * @return string
     */
    public function getEmailNotification2()
    {
      return $this->EmailNotification2;
    }

    /**
     * @param string $EmailNotification2
     * @return \ascio\v3\NameWatchInfo
     */
    public function setEmailNotification2($EmailNotification2)
    {
      $this->EmailNotification2 = $EmailNotification2;
      return $this;
    }

    /**
     * @return string
     */
    public function getEmailNotification3()
    {
      return $this->EmailNotification3;
    }

    /**
     * @param string $EmailNotification3
     * @return \ascio\v3\NameWatchInfo
     */
    public function setEmailNotification3($EmailNotification3)
    {
      $this->EmailNotification3 = $EmailNotification3;
      return $this;
    }

    /**
     * @return string
     */
    public function getEmailNotification4()
    {
      return $this->EmailNotification4;
    }

    /**
     * @param string $EmailNotification4
     * @return \ascio\v3\NameWatchInfo
     */
    public function setEmailNotification4($EmailNotification4)
    {
      $this->EmailNotification4 = $EmailNotification4;
      return $this;
    }

    /**
     * @return string
     */
    public function getEmailNotification5()
    {
      return $this->EmailNotification5;
    }

    /**
     * @param string $EmailNotification5
     * @return \ascio\v3\NameWatchInfo
     */
    public function setEmailNotification5($EmailNotification5)
    {
      $this->EmailNotification5 = $EmailNotification5;
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
     * @return \ascio\v3\NameWatchInfo
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
     * @return \ascio\v3\NameWatchInfo
     */
    public function setReseller($Reseller)
    {
      $this->Reseller = $Reseller;
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
     * @return \ascio\v3\NameWatchInfo
     */
    public function setLabels($Labels)
    {
      $this->Labels = $Labels;
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
     * @return \ascio\v3\NameWatchInfo
     */
    public function setObjectComment($ObjectComment)
    {
      $this->ObjectComment = $ObjectComment;
      return $this;
    }

}
