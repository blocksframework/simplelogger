<?php

namespace System\View;

use System\View;

class NoView extends View {
    public function render(): string {
        return '';
    }
}
