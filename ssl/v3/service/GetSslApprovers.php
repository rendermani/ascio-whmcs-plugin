<?php

namespace ascio\v3;

/**
 * Wrapper class for GetSslApprovers SOAP operation
 */
class GetSslApprovers
{

    /**
     * @var GetSslApproversRequest $request
     */
    protected $request = null;

    /**
     * @param GetSslApproversRequest $request
     */
    public function __construct($request = null)
    {
      $this->request = $request;
    }

    /**
     * @return GetSslApproversRequest
     */
    public function getRequest()
    {
      return $this->request;
    }

    /**
     * @param GetSslApproversRequest $request
     * @return \ascio\v3\GetSslApprovers
     */
    public function setRequest($request)
    {
      $this->request = $request;
      return $this;
    }

}
