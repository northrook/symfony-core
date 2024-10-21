<?php

namespace Core\Controller;

use Core\Controller\Attribute\Template;
use Core\Response\{Document, Parameters};
use Core\DependencyInjection\CoreController;
use Core\Service\{Headers, Request};
use Core\Service\AssetManager\Compiler\{Script, Style};
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route( '/', 'core:public' )]
final class PublicController extends CoreController
{
    // protected function setDefault( Document $document, Parameters $parameters ) : void
    // {
    //     $document(
    //         'Welcome - Public!',
    //         'This is an example template.',
    //     )->assets(
    //         'core',
    //         // 'core.style',
    //     );
    //     // ->asset( 'core', Style::class ) // just use the 'dir.assets/core/*.css' style glob pattern
    //     // ->asset( 'core', Script::class );
    //
    //     $this->response->template = 'welcome.latte';
    // }

    #[
        Route( '/{route}', 'index' ),
        Template( 'index.latte', 'dashboard.latte' )
    ]
    public function index( ?string $route, Document $document, Headers $headers ) : Response
    {
        $headers( 'route-type', 'dynamic' );

        return $this->response( $route );
        //     $document(
        //         'Index says welcome',
        //         keywords: ['we', 'like', 'keywords'],
        //     );
        //     return $this->response->document();
    }

    // public function blog( ?string $route, Document $document, Request $request ) : Response
    // {
    //     $document(
    //         'Demo blog post',
    //         'Assortment of typical blog content for validating the design system.',
    //     );
    //
    //     $this->response->template = 'demo.latte';
    //
    //     return $this->response->document();
    // }
}
