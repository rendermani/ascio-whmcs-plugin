<?php

namespace ascio\v3;

class MarkOrderDocument extends Attachment
{

    /**
     * @var MarkOrderDocType $DocType
     */
    protected $DocType = null;

    /**
     * @param MarkOrderDocType $DocType
     */
    public function __construct($DocType = null)
    {
      parent::__construct();
      $this->DocType = $DocType;
    }

    /**
     * @return MarkOrderDocType
     */
    public function getDocType()
    {
      return $this->DocType;
    }

    /**
     * @param MarkOrderDocType $DocType
     * @return \ascio\v3\MarkOrderDocument
     */
    public function setDocType($DocType)
    {
      $this->DocType = $DocType;
      return $this;
    }

}
