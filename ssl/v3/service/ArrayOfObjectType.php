<?php

namespace ascio\v3;

class ArrayOfObjectType
{

    /**
     * @var ObjectType[] $ObjectType
     */
    protected $ObjectType = null;

    /**
     * @param ObjectType[] $ObjectType
     */
    public function __construct(array $ObjectType = null)
    {
      $this->ObjectType = $ObjectType;
    }

    /**
     * @return ObjectType[]
     */
    public function getObjectType()
    {
      return $this->ObjectType;
    }

    /**
     * @param ObjectType[] $ObjectType
     * @return \ascio\v3\ArrayOfObjectType
     */
    public function setObjectType(array $ObjectType)
    {
      $this->ObjectType = $ObjectType;
      return $this;
    }

}
