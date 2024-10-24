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
    ) : void {}

    #[
        Route( ['/', '/{route}'], 'index' ),
        Template( 'demo.latte' )
    ]
    public function index( ?string $route, Document $document, Headers $headers ) : Response
    {
        $this->auth()->isGranted();
        // We always assume the route is getting [content], and will wrap in a [document] by default.
        // methods with [DocumentResponse] will be parsed before returning the [Response] unless [isHTMX]
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