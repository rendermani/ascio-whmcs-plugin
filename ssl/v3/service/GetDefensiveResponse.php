<?php

namespace ascio\v3;

class GetDefensiveResponse extends AbstractResponse
{

    /**
     * @var DefensiveInfo $DefensiveInfo
     */
    protected $DefensiveInfo = null;

    /**
     * @param int $ResultCode
     */
    public function __construct($ResultCode = null)
    {
      parent::__construct($ResultCode);
    }

    /**
     * @return DefensiveInfo
     */
    public function getDefensiveInfo()
    {
      return $this->DefensiveInfo;
    }

    /**
     * @param DefensiveInfo $DefensiveInfo
     * @return \ascio\v3\GetDefensiveResponse
     */
    public function setDefensiveInfo($DefensiveInfo)
    {
      $this->DefensiveInfo = $DefensiveInfo;
      return $this;
    }

}
