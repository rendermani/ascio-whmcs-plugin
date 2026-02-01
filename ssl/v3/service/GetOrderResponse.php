<?php

namespace ascio\v3;

class GetOrderResponse extends AbstractResponse
{

    /**
     * @var OrderInfo $OrderInfo
     */
    protected $OrderInfo = null;

    /**
     * @param int $ResultCode
     */
    public function __construct($ResultCode = null)
    {
      parent::__construct($ResultCode);
    }

    /**
     * @return OrderInfo
     */
    public function getOrderInfo()
    {
      return $this->OrderInfo;
    }

    /**
     * @param OrderInfo $OrderInfo
     * @return \ascio\v3\GetOrderResponse
     */
    public function setOrderInfo($OrderInfo)
    {
      $this->OrderInfo = $OrderInfo;
      return $this;
    }

}
