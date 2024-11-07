<?php

namespace Core\Controller;

use Core\Framework\Controller;
use Core\Framework\Response\{Document, Parameters};
use Core\Service\Request;
use Northrook\Clerk;
use Symfony\Component\HttpFoundation\Response;

final class AdminController extends Controller
{
    final public function __construct(
        Document $document,
    ) {
        // Clerk::stop( RouteHandler::class );
        Clerk::event( $this::class, 'controller' );

        $document(
            'Welcome - Admin!',
            'This is an example template.',
        );
    }

    public function index( ?string $route, Document $document, Parameters $parameters ) : Response
    {
        $document(
            'Index says welcome',
        );

        return $this->response( $route );
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
