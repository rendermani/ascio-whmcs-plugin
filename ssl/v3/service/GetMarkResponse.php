<?php

namespace ascio\v3;

class GetMarkResponse extends AbstractResponse
{

    /**
     * @var MarkInfo $MarkInfo
     */
    protected $MarkInfo = null;

    /**
     * @param int $ResultCode
     */
    public function __construct($ResultCode = null)
    {
      parent::__construct($ResultCode);
    }

    /**
     * @return MarkInfo
     */
    public function getMarkInfo()
    {
      return $this->MarkInfo;
    }

    /**
     * @param MarkInfo $MarkInfo
     * @return \ascio\v3\GetMarkResponse
     */
    public function setMarkInfo($MarkInfo)
    {
      $this->MarkInfo = $MarkInfo;
      return $this;
    }

}
