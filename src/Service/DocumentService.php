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

    public function renderDocumentHtml(
        null|string|Printable $content = null,
        ?string               $prepend = null,
        ?string               $append = null,
    ) : string {
        
        $string = $this->renderer->render( toString( $content ), $prepend, $append );

        Clerk::stop( __METHOD__ );
        return $string;
    }

    public function getHeadElements() : array
    {
        return $this->renderer->getHead();
    }
}