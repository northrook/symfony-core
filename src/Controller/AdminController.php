<?php

namespace Core\Controller;

use Core\Response\{Controller, Document, Parameters, ResponseHandler};
use Core\DependencyInjection\CoreController;
use Core\Service\Request;
use Northrook\Clerk;
use Symfony\Component\HttpFoundation\Response;

final class AdminController extends CoreController
{

    final public function __construct(
            Document                           $document,
            Parameters                         $parameters,
            protected readonly Request         $request,
            protected readonly ResponseHandler $response,
    ) {
        // Clerk::stop( RouteHandler::class );
        Clerk::event( $this::class, 'controller' );

        $document(
                'Welcome - Admin!',
                'This is an example template.',
        );

        $this->response->template = 'welcome.latte';
    }

    public function index( ?string $route, Document $document, Parameters $parameters ) : Response
    {
        $document(
            'Index says welcome',
        );

        return $this->response->template( $route );
    }

    public function blog( ?string $route, Request $request ) : Response
    {
        dump( $request );
        $message = __METHOD__.' rendering route: '.$route;
        return new Response(
            <<<HTML
                <!DOCTYPE html>
                <html lang="en">
                <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Document</title>
                </head> 
                <body>
                {$message}
                </body> 
                </html> 
                HTML,
        );
    }
}