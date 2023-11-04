<?php

namespace Blocks\System\View;

use Blocks\System\View\Template;

class HtmlPageView extends Template {
    private $htmlPage;

    public function __construct( $html_page, $template ) {
        parent::__construct( $template );

        $this->htmlPage = $html_page;
    }

    public function render(): ?string {
        return $this->htmlPage->render( parent::render() );
    }
}
