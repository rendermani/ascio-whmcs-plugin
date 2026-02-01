<?php

namespace ascio\v3;

class AutoInstallSslOrderRequest extends AbstractOrderRequest
{

    /**
     * @var AutoInstallSsl $AutoInstallSsl
     */
    protected $AutoInstallSsl = null;

    /**
     * @param OrderType $Type
     */
    public function __construct($Type = null)
    {
      parent::__construct($Type);
    }

    /**
     * @return AutoInstallSsl
     */
    public function getAutoInstallSsl()
    {
      return $this->AutoInstallSsl;
    }

    /**
     * @param AutoInstallSsl $AutoInstallSsl
     * @return \ascio\v3\AutoInstallSslOrderRequest
     */
    public function setAutoInstallSsl($AutoInstallSsl)
    {
      $this->AutoInstallSsl = $AutoInstallSsl;
      return $this;
    }

}
