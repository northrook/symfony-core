<?php

declare(strict_types=1);

namespace Core\Response;

use Core\Service\DocumentService;
use LogicException;
use Northrook\Clerk;

final class ResponseHandler
{
    private ?string $content = null;

    private null|array|object $parameters = null;

    /**
     * @param DocumentService $documentService
     */
    public function __construct(
        private DocumentService $documentService,
    ) {
        Clerk::event( $this::class, 'controller' );
    }

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
            $this->documentService,
        );
        Clerk::stopGroup( 'controller' );
        return $response;
    }

    public function html( string $html, bool $override = false ) : self
    {
        $this->assignContent( $html, $override, __METHOD__ );

        return $this;
    }

    public function template( string $template, array|object $parameters = [], bool $override = false ) : self
    {
        $this->assignContent( $template, $override, __METHOD__ );

        $this->parameters = $parameters;
        return $this;
    }

    public function addParameter( string $key, $value ) : self
    {
        $this->parameters[$key] ??= $value;
        return $this;
    }

    public function setParameter( string $key, $value ) : self
    {
        $this->parameters[$key] = $value;
        return $this;
    }

    public function hasParameter( string $key ) : bool
    {
        return \array_key_exists( $key, $this->parameters );
    }

    public function getParameter( string $key ) : mixed
    {
        return $this->parameters[$key] ?? null;
    }

    private function assignContent( string $value, bool $override, string $__METHOD__ ) : void
    {
        if ( $this->content && ! $override ) {
            throw new LogicException( 'The content has already been set.' );
        }
        Clerk::event( $__METHOD__, 'controller' );
        $this->content = $value;
    }
}
