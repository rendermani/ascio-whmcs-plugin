<?php

namespace ascio\v3;

class ArrayOfstring
{

    /**
     * @var string[] $string
     */
    protected $string = null;

    
    public function __construct()
    {
    
    }

    /**
     * @return string[]
     */
    public function getString()
    {
      return $this->string;
    }

    /**
     * @param string[] $string
     * @return \ascio\v3\ArrayOfstring
     */
    public function setString(array $string)
    {
      $this->string = $string;
      return $this;
    }

}
