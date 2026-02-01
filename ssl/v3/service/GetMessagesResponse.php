<?php

namespace ascio\v3;

class GetMessagesResponse extends AbstractResponse
{

    /**
     * @var ArrayOfMessage $Messages
     */
    protected $Messages = null;

    /**
     * @param int $ResultCode
     */
    public function __construct($ResultCode = null)
    {
      parent::__construct($ResultCode);
    }

    /**
     * @return ArrayOfMessage
     */
    public function getMessages()
    {
      return $this->Messages;
    }

    /**
     * @param ArrayOfMessage $Messages
     * @return \ascio\v3\GetMessagesResponse
     */
    public function setMessages($Messages)
    {
      $this->Messages = $Messages;
      return $this;
    }

}
