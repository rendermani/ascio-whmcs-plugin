<?php

namespace ascio\v3;

class GetSslCertificateResponse extends AbstractResponse
{

    /**
     * @var SslCertificateInfo $SslCertificateInfo
     */
    protected $SslCertificateInfo = null;

    /**
     * @param int $ResultCode
     */
    public function __construct($ResultCode = null)
    {
      parent::__construct($ResultCode);
    }

    /**
     * @return SslCertificateInfo
     */
    public function getSslCertificateInfo()
    {
      return $this->SslCertificateInfo;
    }

    /**
     * @param SslCertificateInfo $SslCertificateInfo
     * @return \ascio\v3\GetSslCertificateResponse
     */
    public function setSslCertificateInfo($SslCertificateInfo)
    {
      $this->SslCertificateInfo = $SslCertificateInfo;
      return $this;
    }

}
