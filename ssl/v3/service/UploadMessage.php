<?php

namespace ascio\v3;

class UploadMessage
{

    /**
     * @var UploadMessageRequest $request
     */
    protected $request = null;

    /**
     * @param UploadMessageRequest $request
     */
    public function __construct($request = null)
    {
      $this->request = $request;
    }

    /**
     * @return UploadMessageRequest
     */
    public function getRequest()
    {
      return $this->request;
    }

    /**
     * @param UploadMessageRequest $request
     * @return \ascio\v3\UploadMessage
     */
    public function setRequest($request)
    {
      $this->request = $request;
      return $this;
    }

}
