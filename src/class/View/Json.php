<?php

namespace System\View;

use System\View;

class JsonView extends View {
    public function render(): ?string {
        return json_encode( $this->data, JSON_FORCE_OBJECT );
    }
}
