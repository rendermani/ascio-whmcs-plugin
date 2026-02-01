<?php

namespace ascio\v3;

class GetQueueMessage
{

    /**
     * @var GetQueueMessageRequest $request
     */
    protected $request = null;

    /**
     * @param GetQueueMessageRequest $request
     */
    public function __construct($request = null)
    {
      $this->request = $request;
    }

    /**
     * @return GetQueueMessageRequest
     */
    public function getRequest()
    {
      return $this->request;
    }

    /**
     * @param GetQueueMessageRequest $request
     * @return \ascio\v3\GetQueueMessage
     */
    public function setRequest($request)
    {
      $this->request = $request;
      return $this;
    }

}
