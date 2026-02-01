<?php

namespace ascio\v3;

class ArrayOfMessage
{

    /**
     * @var Message[] $Message
     */
    protected $Message = null;

    
    public function __construct()
    {
    
    }

    /**
     * @return Message[]
     */
    public function getMessage()
    {
      return $this->Message;
    }

    /**
     * @param Message[] $Message
     * @return \ascio\v3\ArrayOfMessage
     */
    public function setMessage(array $Message)
    {
      $this->Message = $Message;
      return $this;
    }

}
