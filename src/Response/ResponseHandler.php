<?php

declare(strict_types=1);

namespace Core\Response;

use Core\DependencyInjection\ServiceContainer;
use Core\Response\Compiler\DocumentHtml;
use Northrook\Clerk;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\CacheInterface;
use const HTTP\OK_200;

final class ResponseHandler
{
    use ServiceContainer;

    public bool $cacheEnabled = true;

    public ?string $template = null;

    /**
     * @param Document       $document
     * @param Parameters     $parameters
     * @param CacheInterface $cache
     * @param DocumentHtml   $documentHtml
     */
    public function __construct(
        protected readonly Document     $document,
        protected readonly Parameters   $parameters,
        private readonly CacheInterface $cache,
        private readonly DocumentHtml   $documentHtml,
    ) {
        Clerk::event( $this::class, 'controller' );
    }

    /**
     * ## Raw HTML response.
     *
     * - Inject Head and Notifications.
     * - Optimizer pass.
     *
     * @param string                $string
     * @param int                   $status
     * @param array<string, string> $headers
     *
     * @return Response
     */
    public function html( string $string, int $status = OK_200, array $headers = [] ) : Response
    {
        return $this->response( $string, $status, $headers );
    }

    /**
     * ## Latte Template response.
     *
     * - Render template.
     * - Inject Head and Notifications.
     * - Optimizer pass.
     *
     * @param null|string $template
     *
     * @return Response
     */
    public function template( ?string $template = null ) : Response
    {
        // This would probably get a great spot for a HTML cache
        // use the $template, $this->document, and $this->parameters as hashKey

        // : The innerHTML / Content
        // ? This can be used to generate meta tags etc
        $content = $this->renderLatte( $template );

        return $this->response( $content );
    }

    /**
     * ## Full Document response
     * Using a Latte Template as content.
     *
     * - Render template.
     * - Render Document.
     * - Inject Notifications.
     * - Optimizer pass.
     *
     * @param ?string $template
     *
     * @return Response
     */
    public function document( ?string $template = null ) : Response
    {
        $content = $this->renderLatte( $template );

        $this->document->add(
            [
                'html.lang'   => 'en',
                'html.id'     => 'top',
                'html.theme'  => $this->document->get( 'theme.name' ) ?? 'system',
                'html.status' => 'init',
            ],
        )
            ->add( 'meta.viewport', 'width=device-width,initial-scale=1' );

        if ( false === $this->document->isPublic ) {
            $this->document->set( 'robots', 'noindex, nofollow' );

            // : Handled by CoreController
            // $this->request()->headers->set( 'X-Robots-Tag', 'noindex, nofollow' );
        }

        $content = $this->documentHtml->document( $content );

        return $this->response( $content );
    }

    /**
     * @param string                $string
     * @param int                   $status
     * @param array<string, string> $headers
     *
     * @return Response
     */
    private function response( string $string, int $status = OK_200, array $headers = [] ) : Response
    {
        $response = new Response( $string, $status, $headers );
        Clerk::event( $this::class.'::asset' )->stop();
        Clerk::stopGroup( 'controller' );
        return $response;
    }

    private function renderLatte( ?string $template ) : string
    {
        if ( $this->template ) {
            $this->parameters->add( 'template', $template );
            $template = $this->template;
        }
        return $this->documentHtml->latte( $template, $this->parameters->getParameters() );
    }
}
