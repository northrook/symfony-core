<?php

namespace Core\Response;

/**
 * ## Auto-routing controller.
 */
abstract class Controller
{
    final public function __construct(
        Document                           $document,
        Parameters                         $parameters,
        protected readonly ResponseHandler $response,
    ) {
        $this->setDefault( $document, $parameters );
    }

    /**
     * @param Document   $document
     * @param Parameters $parameters
     */
    abstract protected function setDefault( Document $document, Parameters $parameters ) : void;
}