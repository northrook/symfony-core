<?php

namespace Core\Controller;

use Core\Response\{Controller, Document, Parameters};
use Core\Service\AssetManager\Asset\Font;
use Core\Service\CurrentRequest;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use const Support\AUTO;

final class PublicController extends Controller
{
    protected function setDefault( Document $document, Parameters $parameters ) : void
    {
        $document(
            'Welcome - Public!',
            'This is an example template.',
        )
            ->assets( 'core', 'admin', ['password' => ['inline' => false, 'preload' => AUTO]], new Font( ['src' => '#'], 'inter' ) )
            ->assets( ['core' => ['inline' => true]] )
            ->assets( ['core' => ['inline' => true]] );

        $this->response->template = 'welcome.latte';
    }

    public function index( ?string $route, Document $document, ParameterBagInterface $parameterBag ) : Response
    {
        dump( $parameterBag->all() );
        $document(
            'Index says welcome',
            keywords: ['we', 'like', 'keywords'],
        );
        return $this->response->document();
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
