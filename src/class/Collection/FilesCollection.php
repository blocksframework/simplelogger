<?php

namespace Blocks\System\Collection;

use Assert\Assert;
use Assert\LazyAssertionException;
use Blocks\Data\Collection;
use Blocks\System\Helper\FilesParam;
use Exception;
use SplFileInfo;

class FilesCollection extends Collection {

    public function __construct(string|array|SplFileInfo $param) {
        $array = FilesParam::get($param);

        parent::__construct($array);
    }

    public function current(): ?SplFileInfo {
        return $this->array[ $this->position ];
    }

}
