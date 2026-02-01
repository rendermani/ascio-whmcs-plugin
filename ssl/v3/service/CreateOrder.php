<?php

namespace ascio\v3;

class CreateOrder
{

    /**
     * @var AbstractOrderRequest $request
     */
    protected $request = null;

    /**
     * @param AbstractOrderRequest $request
     */
    public function __construct($request = null)
    {
      $this->request = $request;
    }

    /**
     * @return AbstractOrderRequest
     */
    public function getRequest()
    {
      return $this->request;
    }

    /**
     * @param AbstractOrderRequest $request
     * @return \ascio\v3\CreateOrder
     */
    public function setRequest($request)
    {
      $this->request = $request;
      return $this;
    }

}
