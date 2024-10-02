<?php

declare(strict_types=1);

namespace Core\Response;

use Northrook\Clerk;

final class ResponseHandler
{
    private ?string $content = null;

    private null|array|object $parameters = null;

    public function __construct() {}

    /**
     * @param ?string $content
     *
     * @return HtmlResponse
     */
    public function __invoke( ?string $content = null ) : HtmlResponse
    {
        $content ??= $this->content;
        $response = new HtmlResponse(
            $content,
            $this->parameters,
            // $this->documentService ?? null,
        );
        Clerk::stopGroup( 'controller' );
        return $response;
    }
}