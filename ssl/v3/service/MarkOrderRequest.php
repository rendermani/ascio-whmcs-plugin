<?php

namespace ascio\v3;

class MarkOrderRequest extends AbstractOrderRequest
{

    /**
     * @var AbstractMark $Mark
     */
    protected $Mark = null;

    /**
     * @var ArrayOfMarkOrderDocument $Documents
     */
    protected $Documents = null;

    /**
     * @param OrderType $Type
     */
    public function __construct($Type = null)
    {
      parent::__construct($Type);
    }

    /**
     * @return AbstractMark
     */
    public function getMark()
    {
      return $this->Mark;
    }

    /**
     * @param AbstractMark $Mark
     * @return \ascio\v3\MarkOrderRequest
     */
    public function setMark($Mark)
    {
      $this->Mark = $Mark;
      return $this;
    }

    /**
     * @return ArrayOfMarkOrderDocument
     */
    public function getDocuments()
    {
      return $this->Documents;
    }

    /**
     * @param ArrayOfMarkOrderDocument $Documents
     * @return \ascio\v3\MarkOrderRequest
     */
    public function setDocuments($Documents)
    {
      $this->Documents = $Documents;
      return $this;
    }

}
