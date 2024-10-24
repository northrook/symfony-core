<?php

namespace Core\DependencyInjection\Trait;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @property UrlGeneratorInterface $urlGenerator
 */
trait UrlGenerator
{
    public function generateRoutePath( string $name, array $parameters = [], bool $relative = false ) : string
    {
        return $this->urlGenerator->generate(
            $name,
            $parameters,
            $relative ? UrlGeneratorInterface::RELATIVE_PATH : UrlGeneratorInterface::ABSOLUTE_PATH,
        );
    }

    public function generateRouteUrl( string $name, array $parameters = [], bool $relative = false ) : string
    {
        return $this->urlGenerator->generate(
            $name,
            $parameters,
            $relative ? UrlGeneratorInterface::NETWORK_PATH : UrlGeneratorInterface::ABSOLUTE_URL,
        );
    }
}