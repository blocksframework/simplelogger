<?php

namespace Blocks\System\Helper;

use SplFileInfo;
use Exception;

class SplFileInfoParam {

    /**
     * Param converter
     *
     * @param string or SplFileInfo
     *
     * @return SplFileInfo
     */
    public static function get(string|SplFileInfo $param) {
        $results = [];

        if ( is_string($param) ) {
            return new SplFileInfo($param);

        } elseif ( $param instanceof SplFileInfo) {
            return $param;

        } else {
            throw new Exception('The passed argument is neither string nor SplFileInfo');
        }
    }

}
