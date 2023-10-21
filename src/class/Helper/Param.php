<?php

namespace Blocks\System\Helper;

use SplFileInfo;
use Exception;

class Param {

    /**
     * Param converter string|array of strings into array of string
     *
     * @param string or array of strings
     *
     * @return array of string
     */
    public static function getStringsParam(array|string $param) {
        $results = [];

        if ( is_array($param) ) {
            foreach ($param as $item) {
                if ( is_string($item) ) {
                    $results[] = $item;
                } else {
                    throw new Exception('A single item of a passed array is not a string');
                }
            }

        } elseif ( is_string($param) ) {
            $results[] = $item;

        } else {
            throw new Exception('An passed argument is not a string');
        }

        return $results;
    }

    /**
     * Param converter string|array of strings|SplFileInfo into array of SplFileInfo
     *
     * @param string or array of strings
     *
     * @return array of string
     */
    public static function getFilesParam(array|string|SplFileInfo $param) {
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
