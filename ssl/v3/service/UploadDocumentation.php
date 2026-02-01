<?php

namespace ascio\v3;

class UploadDocumentation
{

    /**
     * @var UploadDocumentationRequest $request
     */
    protected $request = null;

    /**
     * @param UploadDocumentationRequest $request
     */
    public function __construct($request = null)
    {
      $this->request = $request;
    }

    /**
     * @return UploadDocumentationRequest
     */
    public function getRequest()
    {
      return $this->request;
    }

    /**
     * @param UploadDocumentationRequest $request
     * @return \ascio\v3\UploadDocumentation
     */
    public function setRequest($request)
    {
      $this->request = $request;
      return $this;
    }

}
