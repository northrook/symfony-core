<?php

declare(strict_types=1);

namespace Core\DependencyInjection;

use Core\Response\Attribute\{ContentResponse, DocumentResponse, Template};
use Core\View;
use Core\Response\{Document, Parameters};
use Core\Service\{Headers, Request};
use Northrook\Exception\E_Value;
use Northrook\Latte;
use Northrook\Logger\Log;
use ReflectionException;
use Symfony\Component\HttpFoundation\Response;
use ReflectionClass;
use ReflectionMethod;
use ReflectionAttribute;
use InvalidArgumentException;

/**
 * @internal
 * @author Martin Nielsen <mn@northrook.com>
 */
abstract class CoreController
{
    use ServiceContainer;

    final protected function response( ?string $content = null ) : Response
    {
        // ? This could also be performed on [kernel.finishRequest]
        $this->controllerResponseMethods();

        $content ??= $this->renderLatte();

        $content = $this->responseHtml( $content );


        return new Response( $content );
    }



    private function responseHtml( ?string $content = null ) : string
    {
        // $document = new View\Document( $this->document(), $content );
        //
        // dump( $document );

        return $content;
    }



    /**
     * @return array{document: string, content: ?string}
     */
    final protected function getResponseTemplate() : array
    {
        // Only resolve if no cached version is found
        return $this->resolveResponseTemplate();
    }

    private function renderLatte() : string
    {
        [$template, $content] = \array_values( $this->getResponseTemplate() );

        $this->parameters()->set( 'content', $content );

        return $this->serviceLocator( Latte::class )->render( $template, $this->parameters()->getParameters() );
    }

    /**
     * @return array{document: string, content: ?string}
     */
    private function resolveResponseTemplate() : array
    {
        $caller = $this->request()->controller;

        try {
            $method    = new ReflectionMethod( $caller );
            $attribute = $method->getAttributes( Template::class, ReflectionAttribute::IS_INSTANCEOF )[0]
                         ?? ( new ReflectionClass( $method->class ) )->getAttributes( Template::class )[0] ?? null;
        }
        catch ( ReflectionException $exception ) {
            // TODO : Trigger 404
            E_Value::error(
                'The {controller} route does not exist does provide a template.',
                ['controller' => $caller],
                throw: false,
            );
        }

        // TODO : [low] Cache this value using ArrayStore or other perpetual data store
        //        there should _never_ be any changed to route<=>template relations in production
        $templates = [
            'document' => $attribute->getArguments()[0] ?? throw new InvalidArgumentException( 'A Document template is required.' ),
            'content'  => $attribute->getArguments()[1] ?? null,
        ];

        // dump( $attribute, $templates );

        // if ( null !== $templates['htmx'] && $this->request()->isHtmx ) {
        //     return $templates['htmx'];
        // }
        //
        // return $templates['html'];

        // $route = $this->request()->isHtmx ? $templates['content'] : $templates['document'];
        //
        //
        return $templates;
    }

    private function controllerResponseMethods() : void
    {
        $controller   = new ReflectionClass( $this );
        $responseType = $this->request()->isHtmx ? ContentResponse::class : DocumentResponse::class ;

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



    final protected function request() : Request
    {
        return $this->serviceLocator( Request::class );
    }

    final protected function parameters() : Parameters
    {
        return $this->serviceLocator( Parameters::class );
    }

    final protected function document() : Document
    {
        return $this->serviceLocator( Document::class );
    }
}