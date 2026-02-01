<?php

namespace ascio\v3;

class GetOrders
{

    /**
     * @var GetOrdersRequest $request
     */
    protected $request = null;

    /**
     * @param GetOrdersRequest $request
     */
    public function __construct($request = null)
    {
      $this->request = $request;
    }

    /**
     * @return GetOrdersRequest
     */
    public function getRequest()
    {
      return $this->request;
    }

    /**
     * @param GetOrdersRequest $request
     * @return \ascio\v3\GetOrders
     */
    public function setRequest($request)
    {
      $this->request = $request;
      return $this;
    }

}
