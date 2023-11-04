<?php

namespace Blocks\System\View;

use Blocks\System\View;

class JsonView extends View {
    public function render(): ?string {
        return json_encode( $this->data, JSON_FORCE_OBJECT );
    }
}
