<?php

namespace ascio\v3;

class GetNameWatchResponse extends AbstractResponse
{

    /**
     * @var NameWatchInfo $NameWatchInfo
     */
    protected $NameWatchInfo = null;

    /**
     * @param int $ResultCode
     */
    public function __construct($ResultCode = null)
    {
      parent::__construct($ResultCode);
    }

    /**
     * @return NameWatchInfo
     */
    public function getNameWatchInfo()
    {
      return $this->NameWatchInfo;
    }

    /**
     * @param NameWatchInfo $NameWatchInfo
     * @return \ascio\v3\GetNameWatchResponse
     */
    public function setNameWatchInfo($NameWatchInfo)
    {
      $this->NameWatchInfo = $NameWatchInfo;
      return $this;
    }

}
