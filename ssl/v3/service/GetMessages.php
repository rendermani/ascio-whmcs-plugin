<?php

namespace ascio\v3;

class GetMessages
{

    /**
     * @var GetMessagesRequest $request
     */
    protected $request = null;

    /**
     * @param GetMessagesRequest $request
     */
    public function __construct($request = null)
    {
      $this->request = $request;
    }

    /**
     * @return GetMessagesRequest
     */
    public function getRequest()
    {
      return $this->request;
    }

    /**
     * @param GetMessagesRequest $request
     * @return \ascio\v3\GetMessages
     */
    public function setRequest($request)
    {
      $this->request = $request;
      return $this;
    }

}
