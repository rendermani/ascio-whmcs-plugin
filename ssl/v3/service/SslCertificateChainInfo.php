<?php

namespace ascio\v3;

class SslCertificateChainInfo
{

    /**
     * @var string $Handle
     */
    protected $Handle = null;

    /**
     * @var string $RootCertificate
     */
    protected $RootCertificate = null;

    /**
     * @var string $IntermediateCertificate
     */
    protected $IntermediateCertificate = null;

    /**
     * @var string $Certificate
     */
    protected $Certificate = null;

    /**
     * @var string $FullChain
     */
    protected $FullChain = null;


    public function __construct()
    {

    }

    /**
     * @return string
     */
    public function getHandle()
    {
      return $this->Handle;
    }

    /**
     * @param string $Handle
     * @return \ascio\v3\SslCertificateChainInfo
     */
    public function setHandle($Handle)
    {
      $this->Handle = $Handle;
      return $this;
    }

    /**
     * @return string
     */
    public function getRootCertificate()
    {
      return $this->RootCertificate;
    }

    /**
     * @param string $RootCertificate
     * @return \ascio\v3\SslCertificateChainInfo
     */
    public function setRootCertificate($RootCertificate)
    {
      $this->RootCertificate = $RootCertificate;
      return $this;
    }

    /**
     * @return string
     */
    public function getIntermediateCertificate()
    {
      return $this->IntermediateCertificate;
    }

    /**
     * @param string $IntermediateCertificate
     * @return \ascio\v3\SslCertificateChainInfo
     */
    public function setIntermediateCertificate($IntermediateCertificate)
    {
      $this->IntermediateCertificate = $IntermediateCertificate;
      return $this;
    }

    /**
     * @return string
     */
    public function getCertificate()
    {
      return $this->Certificate;
    }

    /**
     * @param string $Certificate
     * @return \ascio\v3\SslCertificateChainInfo
     */
    public function setCertificate($Certificate)
    {
      $this->Certificate = $Certificate;
      return $this;
    }

    /**
     * @return string
     */
    public function getFullChain()
    {
      return $this->FullChain;
    }

    /**
     * @param string $FullChain
     * @return \ascio\v3\SslCertificateChainInfo
     */
    public function setFullChain($FullChain)
    {
      $this->FullChain = $FullChain;
      return $this;
    }

}
