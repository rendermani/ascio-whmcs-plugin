<?php

namespace ascio\v3;

class GetAutoInstallSslResponse extends AbstractResponse
{

    /**
     * @var AutoInstallSslInfo $AutoInstallSslInfo
     */
    protected $AutoInstallSslInfo = null;

    /**
     * @param int $ResultCode
     */
    public function __construct($ResultCode = null)
    {
      parent::__construct($ResultCode);
    }

    /**
     * @return AutoInstallSslInfo
     */
    public function getAutoInstallSslInfo()
    {
      return $this->AutoInstallSslInfo;
    }

    /**
     * @param AutoInstallSslInfo $AutoInstallSslInfo
     * @return \ascio\v3\GetAutoInstallSslResponse
     */
    public function setAutoInstallSslInfo($AutoInstallSslInfo)
    {
      $this->AutoInstallSslInfo = $AutoInstallSslInfo;
      return $this;
    }

}
