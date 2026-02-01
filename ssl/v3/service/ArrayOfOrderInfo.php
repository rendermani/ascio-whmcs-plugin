<?php

namespace ascio\v3;

class ArrayOfOrderInfo
{

    /**
     * @var OrderInfo[] $OrderInfo
     */
    protected $OrderInfo = null;

    
    public function __construct()
    {
    
    }

    /**
     * @return OrderInfo[]
     */
    public function getOrderInfo()
    {
      return $this->OrderInfo;
    }

    /**
     * @param OrderInfo[] $OrderInfo
     * @return \ascio\v3\ArrayOfOrderInfo
     */
    public function setOrderInfo(array $OrderInfo)
    {
      $this->OrderInfo = $OrderInfo;
      return $this;
    }

}
