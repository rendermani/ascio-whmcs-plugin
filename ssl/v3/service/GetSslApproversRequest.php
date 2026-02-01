<?php

namespace ascio\v3;

/**
 * Request class for GetSslApprovers API
 * Retrieves valid approver email addresses for SSL domain validation
 */
class GetSslApproversRequest
{

    /**
     * @var string $ProductCode SSL product code
     */
    protected $ProductCode = null;

    /**
     * @var string $Name Domain name to get approvers for
     */
    protected $Name = null;


    public function __construct()
    {

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
     * @return \ascio\v3\GetSslApproversRequest
     */
    public function setProductCode($ProductCode)
    {
      $this->ProductCode = $ProductCode;
      return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
      return $this->Name;
    }

    /**
     * @param string $Name
     * @return \ascio\v3\GetSslApproversRequest
     */
    public function setName($Name)
    {
      $this->Name = $Name;
      return $this;
    }

}
