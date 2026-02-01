<?php

namespace ascio\v3;

class GetQueueMessageResponse extends AbstractResponse
{

    /**
     * @var QueueMessage $Message
     */
    protected $Message = null;

    /**
     * @param int $ResultCode
     */
    public function __construct($ResultCode = null)
    {
      parent::__construct($ResultCode);
    }

    /**
     * @return QueueMessage
     */
    public function getMessage()
    {
      return $this->Message;
    }

    /**
     * @param QueueMessage $Message
     * @return \ascio\v3\GetQueueMessageResponse
     */
    public function setMessage($Message)
    {
      $this->Message = $Message;
      return $this;
    }

}
