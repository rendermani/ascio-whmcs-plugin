<?php

namespace ascio\v3;

class QueueMessage
{

    /**
     * @var ArrayOfAttachment $Attachments
     */
    protected $Attachments = null;

    /**
     * @var ArrayOfErrorCode $ErrorCodes
     */
    protected $ErrorCodes = null;

    /**
     * @var int $Id
     */
    protected $Id = null;

    /**
     * @var string $Message
     */
    protected $Message = null;

    /**
     * @var MessageType $MessageType
     */
    protected $MessageType = null;

    /**
     * @var string $ObjectHandle
     */
    protected $ObjectHandle = null;

    /**
     * @var string $ObjectName
     */
    protected $ObjectName = null;

    /**
     * @var ObjectType $ObjectType
     */
    protected $ObjectType = null;

    /**
     * @var string $OrderId
     */
    protected $OrderId = null;

    /**
     * @var OrderStatusType $OrderStatus
     */
    protected $OrderStatus = null;

    /**
     * @var OrderType $OrderType
     */
    protected $OrderType = null;

    /**
     * @param int $Id
     * @param MessageType $MessageType
     * @param OrderStatusType $OrderStatus
     * @param OrderType $OrderType
     */
    public function __construct($Id = null, $MessageType = null, $OrderStatus = null, $OrderType = null)
    {
      $this->Id = $Id;
      $this->MessageType = $MessageType;
      $this->OrderStatus = $OrderStatus;
      $this->OrderType = $OrderType;
    }

    /**
     * @return ArrayOfAttachment
     */
    public function getAttachments()
    {
      return $this->Attachments;
    }

    /**
     * @param ArrayOfAttachment $Attachments
     * @return \ascio\v3\QueueMessage
     */
    public function setAttachments($Attachments)
    {
      $this->Attachments = $Attachments;
      return $this;
    }

    /**
     * @return ArrayOfErrorCode
     */
    public function getErrorCodes()
    {
      return $this->ErrorCodes;
    }

    /**
     * @param ArrayOfErrorCode $ErrorCodes
     * @return \ascio\v3\QueueMessage
     */
    public function setErrorCodes($ErrorCodes)
    {
      $this->ErrorCodes = $ErrorCodes;
      return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
      return $this->Id;
    }

    /**
     * @param int $Id
     * @return \ascio\v3\QueueMessage
     */
    public function setId($Id)
    {
      $this->Id = $Id;
      return $this;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
      return $this->Message;
    }

    /**
     * @param string $Message
     * @return \ascio\v3\QueueMessage
     */
    public function setMessage($Message)
    {
      $this->Message = $Message;
      return $this;
    }

    /**
     * @return MessageType
     */
    public function getMessageType()
    {
      return $this->MessageType;
    }

    /**
     * @param MessageType $MessageType
     * @return \ascio\v3\QueueMessage
     */
    public function setMessageType($MessageType)
    {
      $this->MessageType = $MessageType;
      return $this;
    }

    /**
     * @return string
     */
    public function getObjectHandle()
    {
      return $this->ObjectHandle;
    }

    /**
     * @param string $ObjectHandle
     * @return \ascio\v3\QueueMessage
     */
    public function setObjectHandle($ObjectHandle)
    {
      $this->ObjectHandle = $ObjectHandle;
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
     * @return \ascio\v3\QueueMessage
     */
    public function setObjectName($ObjectName)
    {
      $this->ObjectName = $ObjectName;
      return $this;
    }

    /**
     * @return ObjectType
     */
    public function getObjectType()
    {
      return $this->ObjectType;
    }

    /**
     * @param ObjectType $ObjectType
     * @return \ascio\v3\QueueMessage
     */
    public function setObjectType($ObjectType)
    {
      $this->ObjectType = $ObjectType;
      return $this;
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
     * @return \ascio\v3\QueueMessage
     */
    public function setOrderId($OrderId)
    {
      $this->OrderId = $OrderId;
      return $this;
    }

    /**
     * @return OrderStatusType
     */
    public function getOrderStatus()
    {
      return $this->OrderStatus;
    }

    /**
     * @param OrderStatusType $OrderStatus
     * @return \ascio\v3\QueueMessage
     */
    public function setOrderStatus($OrderStatus)
    {
      $this->OrderStatus = $OrderStatus;
      return $this;
    }

    /**
     * @return OrderType
     */
    public function getOrderType()
    {
      return $this->OrderType;
    }

    /**
     * @param OrderType $OrderType
     * @return \ascio\v3\QueueMessage
     */
    public function setOrderType($OrderType)
    {
      $this->OrderType = $OrderType;
      return $this;
    }

}
