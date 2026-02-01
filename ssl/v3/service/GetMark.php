<?php

namespace ascio\v3;

class GetMark
{

    /**
     * @var GetMarkRequest $request
     */
    protected $request = null;

    /**
     * @param GetMarkRequest $request
     */
    public function __construct($request = null)
    {
      $this->request = $request;
    }

    /**
     * @return GetMarkRequest
     */
    public function getRequest()
    {
      return $this->request;
    }

    /**
     * @param GetMarkRequest $request
     * @return \ascio\v3\GetMark
     */
    public function setRequest($request)
    {
      $this->request = $request;
      return $this;
    }

}
