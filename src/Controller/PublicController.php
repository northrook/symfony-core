<?php

namespace Core\Controller;

use Core\Response\{Controller, Document, Parameters};
use Core\Service\AssetManager\Compiler\{Script, Style};
use Core\Service\Request;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;

final class PublicController extends Controller
{
    protected function setDefault( Document $document, Parameters $parameters ) : void
    {
        $document(
            'Welcome - Public!',
            'This is an example template.',
        )->assets(
            'core',
            // 'core.style',
        );
        // ->asset( 'core', Style::class ) // just use the 'dir.assets/core/*.css' style glob pattern
        // ->asset( 'core', Script::class );

        $this->response->template = 'welcome.latte';
    }

    public function index( ?string $route, Document $document, ParameterBagInterface $parameterBag ) : Response
    {
        // dd( $document->all());
        // dd( $document->assetManager->registerAssets( 'core.style', Style::class ) );
        $document(
            'Index says welcome',
            keywords: ['we', 'like', 'keywords'],
        );
        return $this->response->document();
    }

    public function blog( ?string $route, Request $request ) : Response
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
