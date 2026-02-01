<?php

namespace ascio\v3;

class UploadMessageRequest
{

    /**
     * @var string $OrderId
     */
    protected $OrderId = null;

    /**
     * @var Message $Message
     */
    protected $Message = null;

    
    public function __construct()
    {
    
    }

    /**
     * @return string
     */
    public function getOrderId()
    {
      return $this->OrderId;
    }

    /**
     * @param string $OrderId
     * @return \ascio\v3\UploadMessageRequest
     */
    public function setOrderId($OrderId)
    {
      $this->OrderId = $OrderId;
      return $this;
    }

    /**
     * @return Message
     */
    public function getMessage()
    {
      return $this->Message;
    }

    /**
     * @param Message $Message
     * @return \ascio\v3\UploadMessageRequest
     */
    public function setMessage($Message)
    {
      $this->Message = $Message;
      return $this;
    }

}
