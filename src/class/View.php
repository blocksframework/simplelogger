<?php

namespace System;

abstract class View {
    public $data = [];

    abstract public function render(): ?string;
}
