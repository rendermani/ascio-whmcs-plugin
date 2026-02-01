<?php

namespace ascio\v3;

class PollQueueRequest
{

    /**
     * @var MessageType $MessageType
     */
    protected $MessageType = null;

    /**
     * @var ObjectType $ObjectType
     */
    protected $ObjectType = null;

    
    public function __construct()
    {
    
    }

    /**
     * @return MessageType
     */
    public function getMessageType()
    {
      return $this->MessageType;
    }

    /**
     * @param MessageType $MessageType
     * @return \ascio\v3\PollQueueRequest
     */
    public function setMessageType($MessageType)
    {
      $this->MessageType = $MessageType;
      return $this;
    }

    /**
     * @return ObjectType
     */
    public function getObjectType()
    {
      return $this->ObjectType;
    }

    /**
     * @param ObjectType $ObjectType
     * @return \ascio\v3\PollQueueRequest
     */
    public function setObjectType($ObjectType)
    {
      $this->ObjectType = $ObjectType;
      return $this;
    }

}
