<?php

namespace ascio\v3;

class ArrayOfErrorCode
{

    /**
     * @var ErrorCode[] $ErrorCode
     */
    protected $ErrorCode = null;

    
    public function __construct()
    {
    
    }

    /**
     * @return ErrorCode[]
     */
    public function getErrorCode()
    {
      return $this->ErrorCode;
    }

    /**
     * @param ErrorCode[] $ErrorCode
     * @return \ascio\v3\ArrayOfErrorCode
     */
    public function setErrorCode(array $ErrorCode)
    {
      $this->ErrorCode = $ErrorCode;
      return $this;
    }

}
