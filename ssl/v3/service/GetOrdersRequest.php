<?php

namespace ascio\v3;

class GetOrdersRequest
{

    /**
     * @var string $TransactionComment
     */
    protected $TransactionComment = null;

    /**
     * @var string $Comments
     */
    protected $Comments = null;

    /**
     * @var string $ObjectName
     */
    protected $ObjectName = null;

    /**
     * @var \DateTime $FromDate
     */
    protected $FromDate = null;

    /**
     * @var \DateTime $ToDate
     */
    protected $ToDate = null;

    /**
     * @var ArrayOfOrderStatusType $OrderStatusTypes
     */
    protected $OrderStatusTypes = null;

    /**
     * @var ArrayOfOrderType $OrderTypes
     */
    protected $OrderTypes = null;

    /**
     * @var ArrayOfObjectType $ObjectTypes
     */
    protected $ObjectTypes = null;

    /**
     * @var SearchOrderSortType $OrderSort
     */
    protected $OrderSort = null;

    /**
     * @var PagingInfo $PageInfo
     */
    protected $PageInfo = null;

    /**
     * @param SearchOrderSortType $OrderSort
     */
    public function __construct($OrderSort = null)
    {
      $this->OrderSort = $OrderSort;
    }

    /**
     * @return string
     */
    public function getTransactionComment()
    {
      return $this->TransactionComment;
    }

    /**
     * @param string $TransactionComment
     * @return \ascio\v3\GetOrdersRequest
     */
    public function setTransactionComment($TransactionComment)
    {
      $this->TransactionComment = $TransactionComment;
      return $this;
    }

    /**
     * @return string
     */
    public function getComments()
    {
      return $this->Comments;
    }

    /**
     * @param string $Comments
     * @return \ascio\v3\GetOrdersRequest
     */
    public function setComments($Comments)
    {
      $this->Comments = $Comments;
      return $this;
    }

    /**
     * @return string
     */
    public function getObjectName()
    {
      return $this->ObjectName;
    }

    /**
     * @param string $ObjectName
     * @return \ascio\v3\GetOrdersRequest
     */
    public function setObjectName($ObjectName)
    {
      $this->ObjectName = $ObjectName;
      return $this;
    }

    /**
     * @return \DateTime
     */
    public function getFromDate()
    {
      if ($this->FromDate == null) {
        return null;
      } else {
        try {
          return new \DateTime($this->FromDate);
        } catch (\Exception $e) {
          return false;
        }
      }
    }

    /**
     * @param \DateTime $FromDate
     * @return \ascio\v3\GetOrdersRequest
     */
    public function setFromDate(\DateTime $FromDate)
    {
      $this->FromDate = $FromDate->format(\DateTime::ATOM);
      return $this;
    }

    /**
     * @return \DateTime
     */
    public function getToDate()
    {
      if ($this->ToDate == null) {
        return null;
      } else {
        try {
          return new \DateTime($this->ToDate);
        } catch (\Exception $e) {
          return false;
        }
      }
    }

    /**
     * @param \DateTime $ToDate
     * @return \ascio\v3\GetOrdersRequest
     */
    public function setToDate(\DateTime $ToDate)
    {
      $this->ToDate = $ToDate->format(\DateTime::ATOM);
      return $this;
    }

    /**
     * @return ArrayOfOrderStatusType
     */
    public function getOrderStatusTypes()
    {
      return $this->OrderStatusTypes;
    }

    /**
     * @param ArrayOfOrderStatusType $OrderStatusTypes
     * @return \ascio\v3\GetOrdersRequest
     */
    public function setOrderStatusTypes($OrderStatusTypes)
    {
      $this->OrderStatusTypes = $OrderStatusTypes;
      return $this;
    }

    /**
     * @return ArrayOfOrderType
     */
    public function getOrderTypes()
    {
      return $this->OrderTypes;
    }

    /**
     * @param ArrayOfOrderType $OrderTypes
     * @return \ascio\v3\GetOrdersRequest
     */
    public function setOrderTypes($OrderTypes)
    {
      $this->OrderTypes = $OrderTypes;
      return $this;
    }

    /**
     * @return ArrayOfObjectType
     */
    public function getObjectTypes()
    {
      return $this->ObjectTypes;
    }

    /**
     * @param ArrayOfObjectType $ObjectTypes
     * @return \ascio\v3\GetOrdersRequest
     */
    public function setObjectTypes($ObjectTypes)
    {
      $this->ObjectTypes = $ObjectTypes;
      return $this;
    }

    /**
     * @return SearchOrderSortType
     */
    public function getOrderSort()
    {
      return $this->OrderSort;
    }

    /**
     * @param SearchOrderSortType $OrderSort
     * @return \ascio\v3\GetOrdersRequest
     */
    public function setOrderSort($OrderSort)
    {
      $this->OrderSort = $OrderSort;
      return $this;
    }

    /**
     * @return PagingInfo
     */
    public function getPageInfo()
    {
      return $this->PageInfo;
    }

    /**
     * @param PagingInfo $PageInfo
     * @return \ascio\v3\GetOrdersRequest
     */
    public function setPageInfo($PageInfo)
    {
      $this->PageInfo = $PageInfo;
      return $this;
    }

}
