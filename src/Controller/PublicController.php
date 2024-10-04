<?php

namespace Core\Controller;

use Core\Response\{Controller, Document};
use Core\Service\CurrentRequest;
use Symfony\Component\HttpFoundation\Response;

final class PublicController extends Controller
{
    protected function setDefault( Document $document ) : void
    {
        $document(
            'Welcome - Public!',
            'This is an example template.',
        );

        $this->response->template( 'welcome.latte' );
    }

    public function index( ?string $route, Document $document ) : Response
    {
        $document(
            'Index says welcome',
        );
        $this->response->template( 'welcome.latte' );

        return $this->response->html( 'Hello there' );
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