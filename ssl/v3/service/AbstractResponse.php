<?php

namespace ascio\v3;

abstract class AbstractResponse
{

    /**
     * @var int $ResultCode
     */
    protected $ResultCode = null;

    /**
     * @var string $ResultMessage
     */
    protected $ResultMessage = null;

    /**
     * @var ArrayOfstring $Errors
     */
    protected $Errors = null;

    /**
     * @param int $ResultCode
     */
    public function __construct($ResultCode = null)
    {
      $this->ResultCode = $ResultCode;
    }

    /**
     * @return int
     */
    public function getResultCode()
    {
      return $this->ResultCode;
    }

    /**
     * @param int $ResultCode
     * @return \ascio\v3\AbstractResponse
     */
    public function setResultCode($ResultCode)
    {
      $this->ResultCode = $ResultCode;
      return $this;
    }

    /**
     * @return string
     */
    public function getResultMessage()
    {
      return $this->ResultMessage;
    }

    /**
     * @param string $ResultMessage
     * @return \ascio\v3\AbstractResponse
     */
    public function setResultMessage($ResultMessage)
    {
      $this->ResultMessage = $ResultMessage;
      return $this;
    }

    /**
     * @return ArrayOfstring
     */
    public function getErrors()
    {
      return $this->Errors;
    }

    /**
     * @param ArrayOfstring $Errors
     * @return \ascio\v3\AbstractResponse
     */
    public function setErrors($Errors)
    {
      $this->Errors = $Errors;
      return $this;
    }

}
