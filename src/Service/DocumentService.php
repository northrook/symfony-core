<?php

namespace Core\Service;

use Core\Response\{Document, DocumentHtml};
use Northrook\Clerk;
use Northrook\Interface\Printable;
use Symfony\Component\HttpFoundation\{Request, RequestStack};
use function Support\toString;

final class DocumentService
{
    private DocumentHtml $renderer;

    /**
     * Should be capitalized.
     *
     * Both the  [XML specification](http://www.w3.org/TR/xml/), and [unicode.org](http://unicode.org/resources/utf8.html) are consistent about capitalizing UTF-8.
     *
     * @param RequestStack $requestStack
     * @param Document     $document
     * @param ?string      $encoding     [UTF-8]
     */
    public function __construct(
        private RequestStack $requestStack,
        public Document      $document,
        ?string              $encoding = null,
    ) {
        // $this->request()->headers->set( 'Content-Type', 'text/html; charset='.$encoding );
        // $this->request()->headers->set( 'HX-Request', true );
        // $this->request()->headers->set( 'HX-Assets', 'core-styles,core-scripts,htmx' );
        $this->renderer = new DocumentHtml( $this->document, $encoding );
    }

    public function renderDocumentHtml(
        null|string|Printable $content = null,
        ?string               $prepend = null,
        ?string               $append = null,
    ) : string {
        Clerk::event( $this::class.'::asset' )->stop();
        Clerk::event( __METHOD__, 'document' );

        $this->document->add(
            [
                'html.lang'   => 'en',
                'html.id'     => 'top',
                'html.theme'  => $this->document->get( 'theme.name' ) ?? 'system',
                'html.status' => 'init',
            ],
        )
            ->add( 'meta.viewport', 'width=device-width,initial-scale=1' );

        if ( false === $this->document->isPublic ) {
            $this->document->set( 'robots', 'noindex, nofollow' );

            // : Handled by CoreController
            // $this->request()->headers->set( 'X-Robots-Tag', 'noindex, nofollow' );
        }

        $string = $this->renderer->render( toString( $content ), $prepend, $append );

        Clerk::stop( __METHOD__ );
        return $string;
    }

    public function getHeadElements() : array
    {
        return $this->renderer->getHead();
    }

    private function request() : ?Request
    {
        return $this->requestStack->getCurrentRequest();
    }
}
