<?php

namespace ascio\v3;

class GetOrder
{

    /**
     * @var GetOrderRequest $request
     */
    protected $request = null;

    /**
     * @param GetOrderRequest $request
     */
    public function __construct($request = null)
    {
      $this->request = $request;
    }

    /**
     * @return GetOrderRequest
     */
    public function getRequest()
    {
      return $this->request;
    }

    /**
     * @param GetOrderRequest $request
     * @return \ascio\v3\GetOrder
     */
    public function setRequest($request)
    {
      $this->request = $request;
      return $this;
    }

}
