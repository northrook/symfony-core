<?php

namespace Core\Response;

use Northrook\Clerk;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 * @author Martin Nielsen <mn@northrook.com>
 */
final class HtmlResponse extends Response
{
    private bool $isRendered = false;

    public readonly bool $isTemplate;

    /**
     * @param string            $content
     * @param null|array|object $parameters
     * @param int               $status
     * @param array             $headers
     *                                      // * @param ?DocumentService  $documentService
     */
    public function __construct(
        string                    $content,
        private null|array|object $parameters = [],
        // private readonly ?DocumentService $documentService = null,
        int                       $status = Response::HTTP_OK,
        array                     $headers = [],
    ) {
        Clerk::event( $this::class, 'response' );
        $this->isTemplate = null !== $this->parameters;
        parent::__construct( $content, $status, $headers );
    }
}
