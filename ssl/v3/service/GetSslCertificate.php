<?php

namespace ascio\v3;

class GetSslCertificate
{

    /**
     * @var GetSslCertificateRequest $request
     */
    protected $request = null;

    /**
     * @param GetSslCertificateRequest $request
     */
    public function __construct($request = null)
    {
      $this->request = $request;
    }

    /**
     * @return GetSslCertificateRequest
     */
    public function getRequest()
    {
      return $this->request;
    }

    /**
     * @param GetSslCertificateRequest $request
     * @return \ascio\v3\GetSslCertificate
     */
    public function setRequest($request)
    {
      $this->request = $request;
      return $this;
    }

}
