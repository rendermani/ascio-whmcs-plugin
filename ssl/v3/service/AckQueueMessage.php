<?php

namespace ascio\v3;

class AckQueueMessage
{

    /**
     * @var AckQueueMessageRequest $request
     */
    protected $request = null;

    /**
     * @param AckQueueMessageRequest $request
     */
    public function __construct($request = null)
    {
      $this->request = $request;
    }

    /**
     * @return AckQueueMessageRequest
     */
    public function getRequest()
    {
      return $this->request;
    }

    /**
     * @param AckQueueMessageRequest $request
     * @return \ascio\v3\AckQueueMessage
     */
    public function setRequest($request)
    {
      $this->request = $request;
      return $this;
    }

}
