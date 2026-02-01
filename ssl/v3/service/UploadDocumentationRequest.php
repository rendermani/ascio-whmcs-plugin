<?php

namespace ascio\v3;

class UploadDocumentationRequest
{

    /**
     * @var string $OrderId
     */
    protected $OrderId = null;

    /**
     * @var ArrayOfAttachment $Documents
     */
    protected $Documents = null;

    
    public function __construct()
    {
    
    }

    /**
     * @return string
     */
    public function getOrderId()
    {
      return $this->OrderId;
    }

    /**
     * @param string $OrderId
     * @return \ascio\v3\UploadDocumentationRequest
     */
    public function setOrderId($OrderId)
    {
      $this->OrderId = $OrderId;
      return $this;
    }

    /**
     * @return ArrayOfAttachment
     */
    public function getDocuments()
    {
      return $this->Documents;
    }

    /**
     * @param ArrayOfAttachment $Documents
     * @return \ascio\v3\UploadDocumentationRequest
     */
    public function setDocuments($Documents)
    {
      $this->Documents = $Documents;
      return $this;
    }

}
