<?php

namespace Blocks\System\View;

use Blocks\System\View;

class Template extends View {
    protected $template_path;

    public function __construct( string $template_path ) {
        if ( empty( $template_path ) ) {
            throw new \Exception( 'Class member $template_path can\'t be undefined or an empty string' );
        }

        $this->template_path = $template_path;
    }

    public function render(): ?string {
        $corrected_template_relative_path = ltrim( $this->template_path, '/' );

        $file = DIR_MODULE.$corrected_template_relative_path;

        if ( file_exists( $file ) ) {
            extract( $this->data );

            ob_start();

            require $file;
            $content = ob_get_contents();
            ob_end_clean();

            return $content;
        }

        throw new \Exception( 'Template->fetch(): could not load template "'.$file.'"' );
    }
}
