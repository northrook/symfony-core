<?php

namespace Core\Response;

use Core\Service\DocumentService;
use Northrook\Clerk;
use Symfony\Component\HttpFoundation\{Request, Response};
use const Support\EMPTY_STRING;

/**
 * @internal
 * @author Martin Nielsen <mn@northrook.com>
 */
final class HtmlResponse extends Response
{
    private bool $isRendered = false;

    public readonly bool $isTemplate;

    /**
     * @param string            $content
     * @param null|array|object $parameters
     * @param ?DocumentService  $documentService
     * @param int               $status
     * @param array             $headers
     */
    public function __construct(
        string                            $content,
        private null|array|object         $parameters = [],
        private readonly ?DocumentService $documentService = null,
        int                               $status = Response::HTTP_OK,
        array                             $headers = [],
    ) {
        Clerk::event( $this::class, 'response' );
        $this->isTemplate = null !== $this->parameters;
        parent::__construct( $content, $status, $headers );
    }


    public function render() : void
    {
        if ( $this->isRendered || false === $this->isTemplate ) {
            return;
        }

        $this->content = $this->renderContent();

        $notifications = $this->flashBagHandler();

        $this->assetHandler();

        if ( $this->documentService ) {
            $this->content = $this->documentService->renderDocumentHtml(
                $this->content,
                $notifications,
            );
            Clerk::stopGroup( 'document' );
        }
        else {
            $this->content = $notifications.$this->content;
        }

        $this->isRendered = true;
    }

    private function renderContent() : string
    {
        $latte = $this->latteEnvironment();

        if ( ! $this->documentService ) {
            return $latte->render(
                template   : $this->content,
                parameters : $this->parameters,
            );
        }

        $layout = \strstr( $this->content, '/', true );

        $this->documentService->document->add( 'body.id', $layout )
            ->add( 'body.data-route', $this->request()->route );

        $this->parameters['template'] = $this->content;
        $this->parameters['document'] = $this->documentService;

        return $latte->render(
            template   : "{$layout}.latte",
            parameters : $this->parameters,
        );
    }

    private function assetHandler() : void
    {
        $runtimeAssets = ( new \Northrook\UI\AssetHandler( Get::path( 'dir.assets' ) ) )->getComponentAssets();

        if ( $this->documentService ) {
            $this->documentService->asset( $runtimeAssets, minify : $this->assetHandler->minify );
            return;
        }

        if ( $this->request()->isHtmx ) {
            $assets = EMPTY_STRING;

            foreach ( $runtimeAssets as $asset ) {
                $assets .= $asset->getInlineHtml( true );
            }

            $this->content = $assets.$this->content;
        }
    }
}