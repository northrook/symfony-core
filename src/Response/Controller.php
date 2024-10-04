<?php

namespace Core\Response;

/**
 * ## Auto-routing controller.
 */
abstract class Controller
{
    final public function __construct(
        private readonly Document          $document,
        protected readonly ResponseHandler $response,
    ) {}

    /**
     * @param Document $document
     *
     * @return void
     */
    abstract protected function setDefaults( Document $document ) : void;

    /**
     * @param ?string $content
     *
     * @return HtmlResponse
     */
    final protected function response( ?string $content = null ) : HtmlResponse
    {
        $this->setDefaults( $this->document );

        return $this->response->__invoke( $content );
    }
}
