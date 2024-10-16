<?php

namespace Core\Response;

use Core\Service\{Request};
use Northrook\Clerk;

/**
 * ## Auto-routing controller.
 */
abstract class Controller
{
    final public function __construct(
        Document                           $document,
        Parameters                         $parameters,
        protected readonly Request         $request,
        protected readonly ResponseHandler $response,
    ) {
        Clerk::stop( RouteHandler::class );
        Clerk::event( Controller::class, 'controller' );
        $this->setDefault( $document, $parameters );
    }

    /**
     * @param Document   $document
     * @param Parameters $parameters
     */
    abstract protected function setDefault( Document $document, Parameters $parameters ) : void;
}
