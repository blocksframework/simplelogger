<?php

namespace Blocks\System\View;

use Blocks\System\View;

class Template extends View {
    protected $path;

    public function __construct( string $path ) {
        if ( empty( $path ) ) {
            throw new \Exception( 'Class member $path can\'t be undefined or an empty string' );
        }

        $this->path = $path;
    }

    public function render(): ?string {
        if ( file_exists( $this->path ) ) {
            extract( $this->data );

            ob_start();
            require $this->path;
            $content = ob_get_contents();
            ob_end_clean();

            return $content;
        }

        throw new \Exception( 'Template->render(): could not load template "'.$this->path.'"' );
    }
}
