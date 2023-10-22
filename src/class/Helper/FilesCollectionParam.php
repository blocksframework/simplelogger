<?php

namespace Blocks\System\Helper;

use Blocks\System\Helper\ParamInterface;
use SplFileInfo;
use Exception;

class FilesCollectionParam implements ParamInterface {

    /**
     * Param converter
     *
     * @param string, array of strings, array of SplFileInfo objects or SplFileInfo
     *
     * @return SplFileInfo
     */
    public static function get(string|array|SplFileInfo $param) {
        $results = [];

        if ( is_array($param) ) {
            foreach ($param as $item) {
                if ( is_string($item) ) {
                    $results[] = new SplFileInfo($item);

                } elseif ( $item instanceof SplFileInfo) {
                    $results[] = $item;

                } else {
                    throw new Exception('A single item of a passed array is neither string nor SplFileInfo');
                }
            }

        } elseif ( is_string($param) ) {
            $results[] = new SplFileInfo($item);

        } elseif ( $item instanceof SplFileInfo) {
            $results[] = $item;

        } else {
            throw new Exception('An passed argument is neither string nor SplFileInfo');
        }

        return $results;
    }

}
