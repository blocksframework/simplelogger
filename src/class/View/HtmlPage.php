<?php

namespace System\View;

class HtmlPageView extends HtmlSnippetView {
    private $htmlPage;

    public function __construct( $html_page, $template ) {
        parent::__construct( $template );

        $this->htmlPage = $html_page;
    }

    public function render(): ?string {
        return $this->htmlPage->render( parent::render() );
    }
}
