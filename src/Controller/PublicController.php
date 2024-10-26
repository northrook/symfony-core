<?php

namespace Core\Controller;

use Core\Controller\Attribute\{Template};
use Core\Controller\Attribute\DocumentResponse;
use Core\DependencyInjection\CoreController;
use Core\Response\{Document, Headers, Parameters};
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
    ) : void {}

    #[
        Route( ['/', '/{route}'], 'index' ),
        Template( 'demo.latte' )
    ]
    public function index( ?string $route, Document $document, Headers $headers ) : Response
    {
        $headers( 'route-type', 'dynamic' );

        $document(
            'Index says welcome',
            keywords: ['we', 'like', 'keywords'],
        );

        return $this->response();
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
