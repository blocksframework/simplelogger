<?php

namespace Blocks\System\Helper;

use SplFileInfo;

class SplFileInfoParam {
    /**
     * Param converter.
     *
     * @param string or SplFileInfo
     *
     * @return \SplFileInfo
     */
    public static function get( \SplFileInfo|string $param ) {
        $results = [];

        if ( is_string( $param ) ) {
            return new \SplFileInfo( $param );
        }
        if ( $param instanceof \SplFileInfo ) {
            return $param;
        }

        throw new \Exception( 'The passed argument is neither string nor SplFileInfo' );
    }
}
