<?php

declare(strict_types=1);

namespace Core\Model;

use Core\Response\Document;

final readonly class DocumentParser
{
    private DocumentHead $head;

    private DocumentBody $body;

    public function __construct( private Document $document ) {}

    public function head() : DocumentHead
    {
        return $this->head ??= new DocumentHead( $this->document );
    }

    public function body() : DocumentBody
    {
        return $this->body ??= new DocumentBody( $this->document );
    }
}