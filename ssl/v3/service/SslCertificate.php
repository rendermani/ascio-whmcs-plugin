<?php

namespace ascio\v3;

class SslCertificate
{

    /**
     * @var string $Handle
     */
    protected $Handle = null;

    /**
     * @var string $CommonName
     */
    protected $CommonName = null;

    /**
     * @var string $ProductCode
     */
    protected $ProductCode = null;

    /**
     * @var WebServerType $WebServerType
     */
    protected $WebServerType = null;

    /**
     * @var string $ApproverEmail
     */
    protected $ApproverEmail = null;

    /**
     * @var string $CSR
     */
    protected $CSR = null;

    /**
     * @var Registrant $Owner
     */
    protected $Owner = null;

    /**
     * @var Contact $Admin
     */
    protected $Admin = null;

    /**
     * @var Contact $Tech
     */
    protected $Tech = null;

    /**
     * @var ArrayOfstring $SanNames
     */
    protected $SanNames = null;

    /**
     * @var string $ObjectComment
     */
    protected $ObjectComment = null;

    /**
     * @var SslDomainValidationType $ValidationType
     */
    protected $ValidationType = null;

    
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
     * @return \ascio\v3\SslCertificate
     */
    public function setHandle($Handle)
    {
      $this->Handle = $Handle;
      return $this;
    }

    /**
     * @return string
     */
    public function getCommonName()
    {
      return $this->CommonName;
    }

    /**
     * @param string $CommonName
     * @return \ascio\v3\SslCertificate
     */
    public function setCommonName($CommonName)
    {
      $this->CommonName = $CommonName;
      return $this;
    }

    /**
     * @return string
     */
    public function getProductCode()
    {
      return $this->ProductCode;
    }

    /**
     * @param string $ProductCode
     * @return \ascio\v3\SslCertificate
     */
    public function setProductCode($ProductCode)
    {
      $this->ProductCode = $ProductCode;
      return $this;
    }

    /**
     * @return WebServerType
     */
    public function getWebServerType()
    {
      return $this->WebServerType;
    }

    /**
     * @param WebServerType $WebServerType
     * @return \ascio\v3\SslCertificate
     */
    public function setWebServerType($WebServerType)
    {
      $this->WebServerType = $WebServerType;
      return $this;
    }

    /**
     * @return string
     */
    public function getApproverEmail()
    {
      return $this->ApproverEmail;
    }

    /**
     * @param string $ApproverEmail
     * @return \ascio\v3\SslCertificate
     */
    public function setApproverEmail($ApproverEmail)
    {
      $this->ApproverEmail = $ApproverEmail;
      return $this;
    }

    /**
     * @return string
     */
    public function getCSR()
    {
      return $this->CSR;
    }

    /**
     * @param string $CSR
     * @return \ascio\v3\SslCertificate
     */
    public function setCSR($CSR)
    {
      $this->CSR = $CSR;
      return $this;
    }

    /**
     * @return Registrant
     */
    public function getOwner()
    {
      return $this->Owner;
    }

    /**
     * @param Registrant $Owner
     * @return \ascio\v3\SslCertificate
     */
    public function setOwner($Owner)
    {
      $this->Owner = $Owner;
      return $this;
    }

    /**
     * @return Contact
     */
    public function getAdmin()
    {
      return $this->Admin;
    }

    /**
     * @param Contact $Admin
     * @return \ascio\v3\SslCertificate
     */
    public function setAdmin($Admin)
    {
      $this->Admin = $Admin;
      return $this;
    }

    /**
     * @return Contact
     */
    public function getTech()
    {
      return $this->Tech;
    }

    /**
     * @param Contact $Tech
     * @return \ascio\v3\SslCertificate
     */
    public function setTech($Tech)
    {
      $this->Tech = $Tech;
      return $this;
    }

    /**
     * @return ArrayOfstring
     */
    public function getSanNames()
    {
      return $this->SanNames;
    }

    /**
     * @param ArrayOfstring $SanNames
     * @return \ascio\v3\SslCertificate
     */
    public function setSanNames($SanNames)
    {
      $this->SanNames = $SanNames;
      return $this;
    }

    /**
     * @return string
     */
    public function getObjectComment()
    {
      return $this->ObjectComment;
    }

    /**
     * @param string $ObjectComment
     * @return \ascio\v3\SslCertificate
     */
    public function setObjectComment($ObjectComment)
    {
      $this->ObjectComment = $ObjectComment;
      return $this;
    }

    /**
     * @return SslDomainValidationType
     */
    public function getValidationType()
    {
      return $this->ValidationType;
    }

    /**
     * @param SslDomainValidationType $ValidationType
     * @return \ascio\v3\SslCertificate
     */
    public function setValidationType($ValidationType)
    {
      $this->ValidationType = $ValidationType;
      return $this;
    }

}
