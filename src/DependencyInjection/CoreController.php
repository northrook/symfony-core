<?php

declare(strict_types=1);

namespace Core\DependencyInjection;

use Core\Response\Attribute\{ContentResponse, DocumentResponse};
use Core\View;
use Core\Response\{Document, Parameters};
use Core\Service\{Headers, Request};
use Northrook\Latte;
use Northrook\Logger\Log;
use ReflectionException;
use Symfony\Component\HttpFoundation\Response;
use ReflectionClass;

/**
 * @internal
 * @author Martin Nielsen <mn@northrook.com>
 */
abstract class CoreController
{
    use ServiceContainer;

    final protected function response( ?string $string = null ) : Response
    {
        $this->controllerResponseMethods();

        if ( $string ) {
            return new Response( $string );
        }

        // $content ??= $this->renderLatte();
        // [$template, $content] = \array_values( $this->getResponseTemplate() );

        $template = $this->request->parameters( '_document_template' );
        $content  = $this->request->parameters( '_content_template' );

        dump( $template, $content );

        $this->parameters->set( 'content', $this->request->parameters( '_content_template' ) );

        // return $this->serviceLocator( Latte::class )->render( $template, $this->parameters->getParameters() );

        return new Response( $this->latte->templateToString(
            $this->request->parameters( '_document_template' ),
            $this->parameters->getParameters(),
        ) );
    }

    private function controllerResponseMethods() : void
    {
        $controller   = new ReflectionClass( $this );
        $responseType = $this->request->isHtmx ? ContentResponse::class : DocumentResponse::class ;

        $autowire = \array_keys( $this->serviceLocator->getProvidedServices() );
        // $autowire = [
        //     Headers::class,
        //     Parameters::class,
        //     Document::class,
        // ];

        foreach ( $controller->getMethods() as $method ) {

            if ( ! $method->getAttributes( $responseType ) ) {
                continue;
            }

            $parameters = [];

            foreach ( $method->getParameters() as $parameter ) {
                $injectableClass = $parameter->getType()->__toString();
                if ( \in_array( $injectableClass, $autowire, true ) ) {
                    $parameters[] = $this->serviceLocator->get( $injectableClass );
                }
                else {
                    // TODO : Ensure appropriate exception is thrown on missing dependencies
                    //        nullable parameters will not throw; log in [dev], ignore in [prod]
                    dump( $method );
                }
            }

            try {
                $method->invoke( $this, ...$parameters );
            }
            catch ( ReflectionException $e ) {
                Log::exception( $e );

                continue;
            }
        }
    }

    // final protected function request() : Request
    // {
    //     return $this->serviceLocator( Request::class );
    // }
    //
    // final protected function parameters() : Parameters
    // {
    //     return $this->serviceLocator( Parameters::class );
    // }
    //
    // final protected function document() : Document
    // {
    //     return $this->serviceLocator( Document::class );
    // }
}