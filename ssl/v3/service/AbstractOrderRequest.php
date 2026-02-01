<?php

namespace ascio\v3;

abstract class AbstractOrderRequest
{

    /**
     * @var OrderType $Type
     */
    protected $Type = null;

    /**
     * @var int $Period
     */
    protected $Period = null;

    /**
     * @var string $TransactionComment
     */
    protected $TransactionComment = null;

    /**
     * @var string $Comments
     */
    protected $Comments = null;

    /**
     * @var string $Documentation
     */
    protected $Documentation = null;

    /**
     * @var string $Options
     */
    protected $Options = null;

    /**
     * @param OrderType $Type
     */
    public function __construct($Type = null)
    {
      $this->Type = $Type;
    }

    /**
     * @return OrderType
     */
    public function getType()
    {
      return $this->Type;
    }

    /**
     * @param OrderType $Type
     * @return \ascio\v3\AbstractOrderRequest
     */
    public function setType($Type)
    {
      $this->Type = $Type;
      return $this;
    }

    /**
     * @return int
     */
    public function getPeriod()
    {
      return $this->Period;
    }

    /**
     * @param int $Period
     * @return \ascio\v3\AbstractOrderRequest
     */
    public function setPeriod($Period)
    {
      $this->Period = $Period;
      return $this;
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
     * @return \ascio\v3\AbstractOrderRequest
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
     * @return \ascio\v3\AbstractOrderRequest
     */
    public function setComments($Comments)
    {
      $this->Comments = $Comments;
      return $this;
    }

    /**
     * @return string
     */
    public function getDocumentation()
    {
      return $this->Documentation;
    }

    /**
     * @param string $Documentation
     * @return \ascio\v3\AbstractOrderRequest
     */
    public function setDocumentation($Documentation)
    {
      $this->Documentation = $Documentation;
      return $this;
    }

    /**
     * @return string
     */
    public function getOptions()
    {
      return $this->Options;
    }

    /**
     * @param string $Options
     * @return \ascio\v3\AbstractOrderRequest
     */
    public function setOptions($Options)
    {
      $this->Options = $Options;
      return $this;
    }

}
