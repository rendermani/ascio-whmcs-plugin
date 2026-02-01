<?php

namespace ascio\v3;

class GetSslCertificateChain
{

    /**
     * @var GetSslCertificateChainRequest $request
     */
    protected $request = null;

    /**
     * @param GetSslCertificateChainRequest $request
     */
    public function __construct($request = null)
    {
      $this->request = $request;
    }

    /**
     * @return GetSslCertificateChainRequest
     */
    public function getRequest()
    {
      return $this->request;
    }

    /**
     * @param GetSslCertificateChainRequest $request
     * @return \ascio\v3\GetSslCertificateChain
     */
    public function setRequest($request)
    {
      $this->request = $request;
      return $this;
    }

}
