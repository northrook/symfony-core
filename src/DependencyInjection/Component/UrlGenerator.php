<?php

namespace Core\DependencyInjection\Component;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @property UrlGeneratorInterface $urlGenerator
 */
trait UrlGenerator
{
    public function getPath( string $name, array $parameters = [], bool $relative = false ) : string
    {
        $referenceType = $relative ? UrlGeneratorInterface::RELATIVE_PATH : UrlGeneratorInterface::ABSOLUTE_PATH;
        return $this->urlGenerator->generate( $name, $parameters, $referenceType );
    }

    public function getUrl( string $name, array $parameters = [], bool $relative = false ) : string
    {
        $referenceType = $relative ? UrlGeneratorInterface::NETWORK_PATH : UrlGeneratorInterface::ABSOLUTE_URL;
        return $this->urlGenerator->generate( $name, $parameters, $referenceType );
    }
}
