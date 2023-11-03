<?php

namespace Blocks\System\Collection;

use Blocks\Data\Collection;

class FilesCollection extends Collection {
    /**
     * Constructor.
     *
     * @param string, array of strings, array of SplFileInfo objects or SplFileInfo
     */
    public function __construct( array|\SplFileInfo|string $param ) {
        $array = [];

        if ( is_array( $param ) ) {
            foreach ( $param as $item ) {
                if ( is_string( $item ) ) {
                    $array[] = new \SplFileInfo( $item );
                }
                elseif ( $item instanceof \SplFileInfo ) {
                    $array[] = $item;
                }
                else {
                    throw new \Exception( 'A single item of a passed array is neither string nor SplFileInfo' );
                }
            }
        }
        elseif ( is_string( $param ) ) {
            $array[] = new \SplFileInfo( $item );
        }
        elseif ( $item instanceof \SplFileInfo ) {
            $array[] = $item;
        }
        else {
            throw new \Exception( 'An passed argument is neither string nor SplFileInfo' );
        }

        parent::__construct( $array );
    }

    public function current(): ?\SplFileInfo {
        return $this->array[$this->position];
    }
}
