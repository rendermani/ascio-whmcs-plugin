<?php

namespace ascio\v3;

class UploadMessageResponse extends AbstractResponse
{

    /**
     * @var int $MessageId
     */
    protected $MessageId = null;

    /**
     * @param int $ResultCode
     * @param int $MessageId
     */
    public function __construct($ResultCode = null, $MessageId = null)
    {
      parent::__construct($ResultCode);
      $this->MessageId = $MessageId;
    }

    /**
     * @return int
     */
    public function getMessageId()
    {
      return $this->MessageId;
    }

    /**
     * @param int $MessageId
     * @return \ascio\v3\UploadMessageResponse
     */
    public function setMessageId($MessageId)
    {
      $this->MessageId = $MessageId;
      return $this;
    }

}
