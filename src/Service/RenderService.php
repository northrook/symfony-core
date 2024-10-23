<?php

declare(strict_types=1);

namespace Core\Service;

final class RenderService
{
    public readonly string $documentTemplate;

    public readonly ?string $contentTemplate;

    public function __construct() {}

    public function setTemplates( string $documentTemplate, ?string $contentTemplate ) : void
    {
        $this->documentTemplate = $documentTemplate;
        $this->contentTemplate  = $contentTemplate;
    }

    public function getDocumentTemplate() : string
    {
        return $this->documentTemplate;
    }

    public function getContentTemplate() : string
    {
        return $this->contentTemplate ?? $this->documentTemplate;
    }
}