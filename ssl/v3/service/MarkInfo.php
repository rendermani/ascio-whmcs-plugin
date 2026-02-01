<?php

namespace ascio\v3;

class MarkInfo
{

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
     * @var AbstractMark $Mark
     */
    protected $Mark = null;

    /**
     * @var string $Smd
     */
    protected $Smd = null;

    /**
     * @param \DateTime $Created
     */
    public function __construct(\DateTime $Created = null)
    {
      $this->Created = $Created ? $Created->format(\DateTime::ATOM) : null;
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
     * @return \ascio\v3\MarkInfo
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
     * @return \ascio\v3\MarkInfo
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
     * @return \ascio\v3\MarkInfo
     */
    public function setExpires(\DateTime $Expires)
    {
      $this->Expires = $Expires->format(\DateTime::ATOM);
      return $this;
    }

    /**
     * @return AbstractMark
     */
    public function getMark()
    {
      return $this->Mark;
    }

    /**
     * @param AbstractMark $Mark
     * @return \ascio\v3\MarkInfo
     */
    public function setMark($Mark)
    {
      $this->Mark = $Mark;
      return $this;
    }

    /**
     * @return string
     */
    public function getSmd()
    {
      return $this->Smd;
    }

    /**
     * @param string $Smd
     * @return \ascio\v3\MarkInfo
     */
    public function setSmd($Smd)
    {
      $this->Smd = $Smd;
      return $this;
    }

}
