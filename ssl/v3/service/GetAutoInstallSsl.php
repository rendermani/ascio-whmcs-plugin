<?php

namespace ascio\v3;

class GetAutoInstallSsl
{

    /**
     * @var GetAutoInstallSslRequest $request
     */
    protected $request = null;

    /**
     * @param GetAutoInstallSslRequest $request
     */
    public function __construct($request = null)
    {
      $this->request = $request;
    }

    /**
     * @return GetAutoInstallSslRequest
     */
    public function getRequest()
    {
      return $this->request;
    }

    /**
     * @param GetAutoInstallSslRequest $request
     * @return \ascio\v3\GetAutoInstallSsl
     */
    public function setRequest($request)
    {
      $this->request = $request;
      return $this;
    }

}
