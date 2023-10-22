<?php

namespace Blocks\System\Collection;

use Blocks\Data\Collection;
use Blocks\System\Helper\FilesParam;

class FilesCollection extends Collection {
    public function __construct( array|\SplFileInfo|string $param ) {
        $array = FilesParam::get( $param );

        parent::__construct( $array );
    }

    public function current(): ?\SplFileInfo {
        return $this->array[$this->position];
    }
}
