<?php

namespace ascio\v3;

class AutoInstallSsl
{

    /**
     * @var string $Handle
     */
    protected $Handle = null;

    /**
     * @var string $CommonName
     */
    protected $CommonName = null;

    /**
     * @var string $ProductCode
     */
    protected $ProductCode = null;

    /**
     * @var string $Email
     */
    protected $Email = null;

    /**
     * @var int $SanCount
     */
    protected $SanCount = null;

    /**
     * @var string $ObjectComment
     */
    protected $ObjectComment = null;

    /**
     * @param int $SanCount
     */
    public function __construct($SanCount = null)
    {
      $this->SanCount = $SanCount;
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
     * @return \ascio\v3\AutoInstallSsl
     */
    public function setHandle($Handle)
    {
      $this->Handle = $Handle;
      return $this;
    }

    /**
     * @return string
     */
    public function getCommonName()
    {
      return $this->CommonName;
    }

    /**
     * @param string $CommonName
     * @return \ascio\v3\AutoInstallSsl
     */
    public function setCommonName($CommonName)
    {
      $this->CommonName = $CommonName;
      return $this;
    }

    /**
     * @return string
     */
    public function getProductCode()
    {
      return $this->ProductCode;
    }

    /**
     * @param string $ProductCode
     * @return \ascio\v3\AutoInstallSsl
     */
    public function setProductCode($ProductCode)
    {
      $this->ProductCode = $ProductCode;
      return $this;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
      return $this->Email;
    }

    /**
     * @param string $Email
     * @return \ascio\v3\AutoInstallSsl
     */
    public function setEmail($Email)
    {
      $this->Email = $Email;
      return $this;
    }

    /**
     * @return int
     */
    public function getSanCount()
    {
      return $this->SanCount;
    }

    /**
     * @param int $SanCount
     * @return \ascio\v3\AutoInstallSsl
     */
    public function setSanCount($SanCount)
    {
      $this->SanCount = $SanCount;
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
     * @return \ascio\v3\AutoInstallSsl
     */
    public function setObjectComment($ObjectComment)
    {
      $this->ObjectComment = $ObjectComment;
      return $this;
    }

}
