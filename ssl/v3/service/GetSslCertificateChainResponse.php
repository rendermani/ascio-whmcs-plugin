<?php

namespace ascio\v3;

class GetSslCertificateChainResponse extends AbstractResponse
{

    /**
     * @var SslCertificateChainInfo $SslCertificateChainInfo
     */
    protected $SslCertificateChainInfo = null;

    /**
     * @param int $ResultCode
     */
    public function __construct($ResultCode = null)
    {
      parent::__construct($ResultCode);
    }

    /**
     * @return SslCertificateChainInfo
     */
    public function getSslCertificateChainInfo()
    {
      return $this->SslCertificateChainInfo;
    }

    /**
     * @param SslCertificateChainInfo $SslCertificateChainInfo
     * @return \ascio\v3\GetSslCertificateChainResponse
     */
    public function setSslCertificateChainInfo($SslCertificateChainInfo)
    {
      $this->SslCertificateChainInfo = $SslCertificateChainInfo;
      return $this;
    }

}
