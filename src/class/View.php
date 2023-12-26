<?php

namespace Blocks\System;

abstract class View {
    public $data = [];

    abstract public function render(): ?string;
}
