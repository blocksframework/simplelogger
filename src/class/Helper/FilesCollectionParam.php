<?php

namespace Blocks\System\Helper;

use SplFileInfo;

class FilesCollectionParam {
    /**
     * Param converter.
     *
     * @param string, array of strings, array of SplFileInfo objects or SplFileInfo
     *
     * @return \SplFileInfo
     */
    public static function get( array|\SplFileInfo|string $param ) {
        $results = [];

        if ( is_array( $param ) ) {
            foreach ( $param as $item ) {
                if ( is_string( $item ) ) {
                    $results[] = new \SplFileInfo( $item );
                }
                elseif ( $item instanceof \SplFileInfo ) {
                    $results[] = $item;
                }
                else {
                    throw new \Exception( 'A single item of a passed array is neither string nor SplFileInfo' );
                }
            }
        }
        elseif ( is_string( $param ) ) {
            $results[] = new \SplFileInfo( $item );
        }
        elseif ( $item instanceof \SplFileInfo ) {
            $results[] = $item;
        }
        else {
            throw new \Exception( 'An passed argument is neither string nor SplFileInfo' );
        }

        return $results;
    }
}
