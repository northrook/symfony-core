<?php

namespace Core\Controller;

use Core\Framework\Controller;
use Core\Framework\Response\Document;
use Core\Framework\Response\Headers;
use Core\Framework\Response\Parameters;
use Core\Framework\Controller\{Template, DocumentResponse};
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[
    Route( '/', 'core:public' ),
    Template( 'welcome.latte' ) // wrapping body - like Admin UI
]
final class PublicController extends Controller
{
    #[DocumentResponse]
    protected function onDocumentResponse(
        Document   $document,
        Parameters $parameters,
        Headers    $headers,
    ) : void {
        $document->assets( 'core', 'public' );
    }

    #[
        Route( ['/', '/{route}'], 'index' ),
        Template( 'demo.latte' ) // content template
    ]
    public function index( ?string $route, Document $document, Headers $headers ) : Response
    {
        $headers( 'route-type', 'dynamic' );

        // Toast::info( 'Admin Stylesheet updated.' );
        // Toast::warning( 'Admin Stylesheet updated?!' );
        // Toast::danger( 'Admin Stylesheet updated!!' );
        // Toast::notice( 'Admin Stylesheet updated. 😏' );

        $document(
            'Index says welcome',
            keywords: ['we', 'like', 'keywords'],
        )->body(
            id               : 'admin',
            style            : ['--sidebar-width' => '160px'],
            sidebar_expanded : true,
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
