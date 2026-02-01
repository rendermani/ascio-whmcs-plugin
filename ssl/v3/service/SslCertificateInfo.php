<?php

namespace ascio\v3;

class SslCertificateInfo
{

    /**
     * @var string $Handle
     */
    protected $Handle = null;

    /**
     * @var string $Status
     */
    protected $Status = null;

    /**
     * @var \DateTime $Created
     */
    protected $Created = null;

    /**
     * @var \DateTime $Expires
     */
    protected $Expires = null;

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
     * @var string $Certificate
     */
    protected $Certificate = null;

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

    /**
     * @param \DateTime $Created
     * @param WebServerType $WebServerType
     * @param SslDomainValidationType $ValidationType
     */
    public function __construct(\DateTime $Created = null, $WebServerType = null, $ValidationType = null)
    {
      $this->Created = $Created ? $Created->format(\DateTime::ATOM) : null;
      $this->WebServerType = $WebServerType;
      $this->ValidationType = $ValidationType;
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
     * @return \ascio\v3\SslCertificateInfo
     */
    public function setHandle($Handle)
    {
      $this->Handle = $Handle;
      return $this;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
      return $this->Status;
    }

    /**
     * @param string $Status
     * @return \ascio\v3\SslCertificateInfo
     */
    public function setStatus($Status)
    {
      $this->Status = $Status;
      return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreated()
    {
      if ($this->Created == null) {
        return null;
      } else {
        try {
          return new \DateTime($this->Created);
        } catch (\Exception $e) {
          return false;
        }
      }
    }

    /**
     * @param \DateTime $Created
     * @return \ascio\v3\SslCertificateInfo
     */
    public function setCreated(\DateTime $Created)
    {
      $this->Created = $Created->format(\DateTime::ATOM);
      return $this;
    }

    /**
     * @return \DateTime
     */
    public function getExpires()
    {
      if ($this->Expires == null) {
        return null;
      } else {
        try {
          return new \DateTime($this->Expires);
        } catch (\Exception $e) {
          return false;
        }
      }
    }

    /**
     * @param \DateTime $Expires
     * @return \ascio\v3\SslCertificateInfo
     */
    public function setExpires(\DateTime $Expires)
    {
      $this->Expires = $Expires->format(\DateTime::ATOM);
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
     * @return \ascio\v3\SslCertificateInfo
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
     * @return \ascio\v3\SslCertificateInfo
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
     * @return \ascio\v3\SslCertificateInfo
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
     * @return \ascio\v3\SslCertificateInfo
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
     * @return \ascio\v3\SslCertificateInfo
     */
    public function setCSR($CSR)
    {
      $this->CSR = $CSR;
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
     * @return \ascio\v3\SslCertificateInfo
     */
    public function setCertificate($Certificate)
    {
      $this->Certificate = $Certificate;
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
     * @return \ascio\v3\SslCertificateInfo
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
     * @return \ascio\v3\SslCertificateInfo
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
     * @return \ascio\v3\SslCertificateInfo
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
     * @return \ascio\v3\SslCertificateInfo
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
     * @return \ascio\v3\SslCertificateInfo
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
     * @return \ascio\v3\SslCertificateInfo
     */
    public function setValidationType($ValidationType)
    {
      $this->ValidationType = $ValidationType;
      return $this;
    }

}
