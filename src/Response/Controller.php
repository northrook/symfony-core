<?php

namespace Core\Response;

use Symfony\Component\HttpFoundation\Response;

/**
 * ## Auto-routing controller.
 */
abstract class Controller
{
    final public function router( ?string $route ) : Response
    {
        dump( $this );
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
                    $message
                    </body> 
                    </html> 
                    
                    HTML,

        );
    }
}