<?php

namespace ascio\v3;

class NameWatchOrderRequest extends AbstractOrderRequest
{

    /**
     * @var NameWatch $NameWatch
     */
    protected $NameWatch = null;

    /**
     * @param OrderType $Type
     */
    public function __construct($Type = null)
    {
      parent::__construct($Type);
    }

    /**
     * @return NameWatch
     */
    public function getNameWatch()
    {
      return $this->NameWatch;
    }

    /**
     * @param NameWatch $NameWatch
     * @return \ascio\v3\NameWatchOrderRequest
     */
    public function setNameWatch($NameWatch)
    {
      $this->NameWatch = $NameWatch;
      return $this;
    }

}
