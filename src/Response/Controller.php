<?php

namespace Core\Response;

/**
 * ## Auto-routing controller.
 */
abstract class Controller
{
    final public function __construct(
        protected readonly ResponseHandler $response,
    ) {
        $this->setDefaults( $this->response->document );
    }

    final protected function template() : HtmlResponse
    {

    }

    /**
     * @param Document $document
     */
    abstract protected function setDefaults( Document $document ) : void;

    /**
     * @param ?string                     $content
     * @param array<string, mixed>|object $parameters
     *
     * @return HtmlResponse
     */
    final protected function response( ?string $content = null, array|object $parameters = [] ) : HtmlResponse
    {
        $this->response->assignContent( $content, $parameters, __METHOD__ );

        return $this->response->__invoke( $content );
    }
}