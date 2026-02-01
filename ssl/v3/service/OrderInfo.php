<?php

namespace ascio\v3;

class OrderInfo
{

    /**
     * @var string $OrderId
     */
    protected $OrderId = null;

    /**
     * @var OrderStatusType $Status
     */
    protected $Status = null;

    /**
     * @var \DateTime $Created
     */
    protected $Created = null;

    /**
     * @var AbstractOrderRequest $OrderRequest
     */
    protected $OrderRequest = null;

    /**
     * @param OrderStatusType $Status
     * @param \DateTime $Created
     */
    public function __construct($Status = null, \DateTime $Created = null)
    {
      $this->Status = $Status;
      $this->Created = $Created ? $Created->format(\DateTime::ATOM) : null;
    }

    /**
     * @return string
     */
    public function getOrderId()
    {
      return $this->OrderId;
    }

    /**
     * @param string $OrderId
     * @return \ascio\v3\OrderInfo
     */
    public function setOrderId($OrderId)
    {
      $this->OrderId = $OrderId;
      return $this;
    }

    /**
     * @return OrderStatusType
     */
    public function getStatus()
    {
      return $this->Status;
    }

    /**
     * @param OrderStatusType $Status
     * @return \ascio\v3\OrderInfo
     */
    public function setStatus($Status)
    {
      $this->Status = $Status;
      return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreated()
    {
      if ($this->Created == null) {
        return null;
      } else {
        try {
          return new \DateTime($this->Created);
        } catch (\Exception $e) {
          return false;
        }
      }
    }

    /**
     * @param \DateTime $Created
     * @return \ascio\v3\OrderInfo
     */
    public function setCreated(\DateTime $Created)
    {
      $this->Created = $Created->format(\DateTime::ATOM);
      return $this;
    }

    /**
     * @return AbstractOrderRequest
     */
    public function getOrderRequest()
    {
      return $this->OrderRequest;
    }

    /**
     * @param AbstractOrderRequest $OrderRequest
     * @return \ascio\v3\OrderInfo
     */
    public function setOrderRequest($OrderRequest)
    {
      $this->OrderRequest = $OrderRequest;
      return $this;
    }

}
