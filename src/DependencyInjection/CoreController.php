<?php

declare(strict_types=1);

namespace Core\DependencyInjection;

use Core\Response\{Document, Parameters};
use Core\Service\Headers;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 * @author Martin Nielsen <mn@northrook.com>
 */
abstract class CoreController
{
    use ServiceContainer;

    protected function onDocumentResponse(
        Document   $document,
        Parameters $parameters,
        Headers    $headers,
    ) : void {}

    final protected function response(
            string $content
    ) : Response
    {
        dump( __METHOD__);
        return new Response($content);
    }
}