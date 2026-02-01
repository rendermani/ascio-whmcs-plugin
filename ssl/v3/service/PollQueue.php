<?php

namespace ascio\v3;

class PollQueue
{

    /**
     * @var PollQueueRequest $request
     */
    protected $request = null;

    /**
     * @param PollQueueRequest $request
     */
    public function __construct($request = null)
    {
      $this->request = $request;
    }

    /**
     * @return PollQueueRequest
     */
    public function getRequest()
    {
      return $this->request;
    }

    /**
     * @param PollQueueRequest $request
     * @return \ascio\v3\PollQueue
     */
    public function setRequest($request)
    {
      $this->request = $request;
      return $this;
    }

}
