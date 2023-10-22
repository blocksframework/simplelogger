<?php

namespace System\View;

use System\View;

class HtmlSnippetView extends View {
    protected $template;

    public function __construct(string $template) {
        if ( !present($template) ) {
            trigger_error_in_class('Class member $template can\'t be undefined or an empty string');
        }

        $this->template = $template;
    }

    public function render(): ?string {
        $corrected_template_relative_path = ltrim($this->template, '/');

        $file = DIR_MODULE . $corrected_template_relative_path;

        if ( file_exists($file) ) {
            extract($this->data);

            ob_start();
            require($file);
            $content = ob_get_contents();
            ob_end_clean();

            return $content;

        } else {
            trigger_error_in_class('Template->fetch(): could not load template "' . $file . '"');
        }
    }

}