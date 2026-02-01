<?php

namespace ascio\v3;

class Message
{

    /**
     * @var ArrayOfAttachment $Attachments
     */
    protected $Attachments = null;

    /**
     * @var string $Body
     */
    protected $Body = null;

    /**
     * @var \DateTime $Created
     */
    protected $Created = null;

    /**
     * @var string $FromAddress
     */
    protected $FromAddress = null;

    /**
     * @var string $Subject
     */
    protected $Subject = null;

    /**
     * @var string $ToAddress
     */
    protected $ToAddress = null;

    /**
     * @var MessageType $Type
     */
    protected $Type = null;

    /**
     * @param \DateTime $Created
     * @param MessageType $Type
     */
    public function __construct(\DateTime $Created = null, $Type = null)
    {
      $this->Created = $Created ? $Created->format(\DateTime::ATOM) : null;
      $this->Type = $Type;
    }

    /**
     * @return ArrayOfAttachment
     */
    public function getAttachments()
    {
      return $this->Attachments;
    }

    /**
     * @param ArrayOfAttachment $Attachments
     * @return \ascio\v3\Message
     */
    public function setAttachments($Attachments)
    {
      $this->Attachments = $Attachments;
      return $this;
    }

    /**
     * @return string
     */
    public function getBody()
    {
      return $this->Body;
    }

    /**
     * @param string $Body
     * @return \ascio\v3\Message
     */
    public function setBody($Body)
    {
      $this->Body = $Body;
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
     * @return \ascio\v3\Message
     */
    public function setCreated(\DateTime $Created)
    {
      $this->Created = $Created->format(\DateTime::ATOM);
      return $this;
    }

    /**
     * @return string
     */
    public function getFromAddress()
    {
      return $this->FromAddress;
    }

    /**
     * @param string $FromAddress
     * @return \ascio\v3\Message
     */
    public function setFromAddress($FromAddress)
    {
      $this->FromAddress = $FromAddress;
      return $this;
    }

    /**
     * @return string
     */
    public function getSubject()
    {
      return $this->Subject;
    }

    /**
     * @param string $Subject
     * @return \ascio\v3\Message
     */
    public function setSubject($Subject)
    {
      $this->Subject = $Subject;
      return $this;
    }

    /**
     * @return string
     */
    public function getToAddress()
    {
      return $this->ToAddress;
    }

    /**
     * @param string $ToAddress
     * @return \ascio\v3\Message
     */
    public function setToAddress($ToAddress)
    {
      $this->ToAddress = $ToAddress;
      return $this;
    }

    /**
     * @return MessageType
     */
    public function getType()
    {
      return $this->Type;
    }

    /**
     * @param MessageType $Type
     * @return \ascio\v3\Message
     */
    public function setType($Type)
    {
      $this->Type = $Type;
      return $this;
    }

}
