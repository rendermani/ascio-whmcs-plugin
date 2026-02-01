<?php

namespace ascio\v3;

class GetDefensive
{

    /**
     * @var GetDefensiveRequest $request
     */
    protected $request = null;

    /**
     * @param GetDefensiveRequest $request
     */
    public function __construct($request = null)
    {
      $this->request = $request;
    }

    /**
     * @return GetDefensiveRequest
     */
    public function getRequest()
    {
      return $this->request;
    }

    /**
     * @param GetDefensiveRequest $request
     * @return \ascio\v3\GetDefensive
     */
    public function setRequest($request)
    {
      $this->request = $request;
      return $this;
    }

}
