<?php

declare(strict_types=1);

namespace Core\Response;

use Core\Service\CurrentRequest;
use Core\View\Message;
use Northrook\Clerk;
use Closure;
use Northrook\HTML\Element\Attributes;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\CacheInterface;
use function String\hashKey;
use function Support\toString;
use const HTTP\OK_200;
use InvalidArgumentException;
use const Support\EMPTY_STRING;
use const Support\WHITESPACE;

final class ResponseHandler
{
    public bool $cacheEnabled = true;

    public ?string $template = null;

    /**
     * @param Document       $document
     * @param Parameters     $parameters
     * @param CacheInterface $cache
     * @param CurrentRequest $request
     * @param Closure        $lazyLatte
     */
    public function __construct(
        protected readonly Document     $document,
        protected readonly Parameters   $parameters,
        private readonly CacheInterface $cache,
        private readonly CurrentRequest $request,
        private readonly Closure        $lazyLatte,
    ) {
        Clerk::event( $this::class, 'controller' );
    }

    /**
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
        $content = $this->latte( $template );

        return $this->response( $content );
    }

    /**
     * @param ?string $template
     *
     * @return Response
     */
    public function document( ?string $template = null ) : Response
    {
        // : The innerHTML / Content
        // ? This can be used to generate meta tags etc
        $content = $this->latte( $template );

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

        return $this->response( $content );
    }

    /**
     * @param string $template
     *
     * @return string
     */
    protected function latte( string $template ) : string
    {
        if ( ! \str_ends_with( $template, '.latte' ) ) {
            throw new InvalidArgumentException( "The '{$template}' string is not valid.\nIt should end with '.latte' and point to a valid template file.}'" );
        }

        if ( $this->template ) {
            $this->parameters->set( 'template', $template );
            $template = $this->template;
        }

        $hashKey = hashKey( [$template, $this->document, $this->parameters] );
        $latte   = ( $this->lazyLatte )();
        $content = $latte->render( $template, $this->parameters->getParameters() );

        dump( $hashKey, $latte, $content );

        return $content;
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

    private function flashBagHandler() : string
    {
        $flashes       = $this->request->flashBag()->all();
        $notifications = EMPTY_STRING;

        foreach ( $flashes as $type => $flash ) {
            foreach ( $flash as $toast ) {
                dump( $toast );
                // $notification = match ( $toast instanceof Message ) {
                //     true => new Notification(
                //         $toast->type,
                //         $toast->message,
                //         $toast->description,
                //         $toast->timeout,
                //     ),
                //     false => new Notification(
                //         $type,
                //         toString( $toast ),
                //     ),
                // };
                //
                // if ( ! $notification->description ) {
                //     $notification->attributes->add( 'class', 'compact' );
                // }
                //
                // if ( ! $notification->timeout && 'error' !== $notification->type ) {
                //     $notification->setTimeout( Settings::get( 'notification.timeout' ) ?? 5_000 );
                // }
                // $notifications .= $notification;

            }
        }

        return $notifications;
    }

    private function getDocumentHtml( string ...$content ) : string
    {
        $this->document->

        $attributes = $this->document->pull( 'html' );

        return toString(
                [
                        '<!DOCTYPE html>',
                        '<html'.$this->documentHtmlAttributes() . '>',
                        ...$this->documentHead(),
                        ...$this->documentBody( $content ),
                        '</html>',
                ],
                PHP_EOL,
        );
    }

    private function documentHtmlAttributes() : string
    {
        $attributes = $this->document->pull( 'html' );
        if ( ! $attributes || ! \is_array( $attributes ) ) {
            return EMPTY_STRING;
        }
        return WHITESPACE.Attributes::from( $attributes );
    }

    private function headElements()
    {

    }
}