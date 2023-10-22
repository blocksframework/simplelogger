<?php

namespace Blocks\System\Helper;

interface ParamInterface {

    /**
     * Param converter
     *
     * @param some param(s)
     *
     * @return some param
     */
    #[\ReturnTypeWillChange]
    public static function get($param);

}
