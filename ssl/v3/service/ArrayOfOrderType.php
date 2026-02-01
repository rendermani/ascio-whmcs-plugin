<?php

namespace ascio\v3;

class ArrayOfOrderType
{

    /**
     * @var OrderType[] $OrderType
     */
    protected $OrderType = null;

    /**
     * @param OrderType[] $OrderType
     */
    public function __construct(array $OrderType = null)
    {
      $this->OrderType = $OrderType;
    }

    /**
     * @return OrderType[]
     */
    public function getOrderType()
    {
      return $this->OrderType;
    }

    /**
     * @param OrderType[] $OrderType
     * @return \ascio\v3\ArrayOfOrderType
     */
    public function setOrderType(array $OrderType)
    {
      $this->OrderType = $OrderType;
      return $this;
    }

}
