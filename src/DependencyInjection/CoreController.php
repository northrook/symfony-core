<?php

declare(strict_types=1);

namespace Core\DependencyInjection;

use Core\Response\{Attribute\DocumentResponse, Attribute\Template};
use Core\Service\{Request};
use Northrook\Exception\E_Value;
use ReflectionException;
use Symfony\Component\HttpFoundation\Response;
use ReflectionClass;
use ReflectionMethod;
use BadMethodCallException;
use ReflectionAttribute;

/**
 * @internal
 * @author Martin Nielsen <mn@northrook.com>
 */
abstract class CoreController
{
    use ServiceContainer;

    final protected function response(
        string $content,
    ) : Response {
        $template = $this->resolveResponseTemplate();
        $this->documentResponseMethods();
        dump( $template );
        return new Response( $content );
    }

    private function documentResponseMethods() : void
    {
        $controller ??= new ReflectionClass( $this );

        $methods = [];

        foreach ( $controller->getAttributes( DocumentResponse::class ) as $attribute ) {
            // we need to find each, check if they require valid injections, if so, inject and call.
            dump( $attribute );
        }
    }

    private function resolveResponseTemplate() : ?string
    {
        $caller = $this->request()->controller;

        try {
            $method    = new ReflectionMethod( $caller );
            $attribute = $method->getAttributes( Template::class, ReflectionAttribute::IS_INSTANCEOF )[0]
                         ?? ( new ReflectionClass( $method->class ) )->getAttributes( Template::class )[0] ?? null;
        }
        catch ( ReflectionException $exception ) {
            return E_Value::error(
                'The {controller} route does not exist does provide a template.',
                ['controller' => $caller],
                throw: false,
            );
        }

        // TODO : [low] Cache this value
        $templates = $attribute->getArguments();

        [$document, $content] = $templates;

        $route = $this->request()->isHtmx ? $content : $document;

        // try {
        // }
        // catch ( ReflectionException $exception ) {
        //     dump( $exception->getMessage() );
        // }
        dump( $attribute, $templates, $route );

        // dump( $attribute[0]->getArguments() );

        return $route;
    }

    final protected function request() : Request
    {
        return $this->serviceLocator( Request::class );
    }
}