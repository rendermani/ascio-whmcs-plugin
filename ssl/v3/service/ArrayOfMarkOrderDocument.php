<?php

namespace ascio\v3;

class ArrayOfMarkOrderDocument
{

    /**
     * @var MarkOrderDocument[] $MarkOrderDocument
     */
    protected $MarkOrderDocument = null;

    
    public function __construct()
    {
    
    }

    /**
     * @return MarkOrderDocument[]
     */
    public function getMarkOrderDocument()
    {
      return $this->MarkOrderDocument;
    }

    /**
     * @param MarkOrderDocument[] $MarkOrderDocument
     * @return \ascio\v3\ArrayOfMarkOrderDocument
     */
    public function setMarkOrderDocument(array $MarkOrderDocument)
    {
      $this->MarkOrderDocument = $MarkOrderDocument;
      return $this;
    }

}
