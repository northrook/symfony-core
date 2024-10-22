<?php

namespace Core\Controller;

use Core\DependencyInjection\CoreController;
use Core\Response\{Attribute\DocumentResponse, Document, Parameters};
use Core\Response\Attribute\Template;
use Core\Service\{Headers};
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[
    Route( '/', 'core:public' ),
    Template( 'welcome.latte' )
]
final class PublicController extends CoreController
{
    #[DocumentResponse]
    protected function onDocumentResponse(
            Document   $document,
            Parameters $parameters,
            Headers    $headers,
    ) : void {

    }

    #[
        Route( '/{route}', 'index' ),
        Template( 'demo.latte' )
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