<?php

namespace ascio\v3;

class SslCertificateOrderRequest extends AbstractOrderRequest
{

    /**
     * @var SslCertificate $SslCertificate
     */
    protected $SslCertificate = null;

    /**
     * @param OrderType $Type
     */
    public function __construct($Type = null)
    {
      parent::__construct($Type);
    }

    /**
     * @return SslCertificate
     */
    public function getSslCertificate()
    {
      return $this->SslCertificate;
    }

    /**
     * @param SslCertificate $SslCertificate
     * @return \ascio\v3\SslCertificateOrderRequest
     */
    public function setSslCertificate($SslCertificate)
    {
      $this->SslCertificate = $SslCertificate;
      return $this;
    }

}
