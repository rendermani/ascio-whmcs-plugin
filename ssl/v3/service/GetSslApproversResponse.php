<?php

namespace ascio\v3;

/**
 * Response class for GetSslApprovers API
 * Contains the list of valid approver email addresses
 */
class GetSslApproversResponse extends AbstractResponse
{

    /**
     * @var ArrayOfstring $ApproverEmails List of valid approver email addresses
     */
    protected $ApproverEmails = null;

    /**
     * @param int $ResultCode
     */
    public function __construct($ResultCode = null)
    {
      parent::__construct($ResultCode);
    }

    /**
     * @return ArrayOfstring
     */
    public function getApproverEmails()
    {
      return $this->ApproverEmails;
    }

    /**
     * @param ArrayOfstring $ApproverEmails
     * @return \ascio\v3\GetSslApproversResponse
     */
    public function setApproverEmails($ApproverEmails)
    {
      $this->ApproverEmails = $ApproverEmails;
      return $this;
    }

}
