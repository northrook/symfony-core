<?php

namespace Core\Controller;

use Core\Response\{Controller, Document};
use Core\Service\CurrentRequest;
use Symfony\Component\HttpFoundation\Response;

final class PublicController extends Controller
{
    protected function setDefaults( Document $document ) : void
    {
        $document(
            'Welcome!',
            'This is an example template.',
        );
    }

    public function index( ?string $route ) : Response
    {
        $this->response->template( 'welcome.latte' );

        return $this->response( 'Hello there' );
    }

    public function blog( ?string $route, CurrentRequest $request ) : Response
    {
        dump( $request );
        $message = __METHOD__.' rendering route: '.$route ;
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
