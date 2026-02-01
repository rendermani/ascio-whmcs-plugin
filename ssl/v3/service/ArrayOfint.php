<?php

namespace ascio\v3;

class ArrayOfint
{

    /**
     * @var int[] $int
     */
    protected $int = null;

    /**
     * @param int[] $int
     */
    public function __construct(array $int = null)
    {
      $this->int = $int;
    }

    /**
     * @return int[]
     */
    public function getInt()
    {
      return $this->int;
    }

    /**
     * @param int[] $int
     * @return \ascio\v3\ArrayOfint
     */
    public function setInt(array $int)
    {
      $this->int = $int;
      return $this;
    }

}
