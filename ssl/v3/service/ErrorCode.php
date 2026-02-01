<?php

namespace ascio\v3;

class ErrorCode
{

    /**
     * @var string $Code
     */
    protected $Code = null;

    /**
     * @var string $Message
     */
    protected $Message = null;

    
    public function __construct()
    {
    
    }

    /**
     * @return string
     */
    public function getCode()
    {
      return $this->Code;
    }

    /**
     * @param string $Code
     * @return \ascio\v3\ErrorCode
     */
    public function setCode($Code)
    {
      $this->Code = $Code;
      return $this;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
      return $this->Message;
    }

    /**
     * @param string $Message
     * @return \ascio\v3\ErrorCode
     */
    public function setMessage($Message)
    {
      $this->Message = $Message;
      return $this;
    }

}
