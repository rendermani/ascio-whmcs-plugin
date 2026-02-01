<?php

namespace ascio\v3;

class ValidateOrderResponse extends AbstractResponse
{

    /**
     * @param int $ResultCode
     */
    public function __construct($ResultCode = null)
    {
      parent::__construct($ResultCode);
    }

}
