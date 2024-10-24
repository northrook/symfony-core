<?php

declare(strict_types=1);

namespace Core\Latte\Extension;

use Core\DependencyInjection\Trait\UrlGenerator;
use Latte\Extension;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class UrlGeneratorExtension extends Extension
{
    use UrlGenerator;

    /**
     * @param UrlGeneratorInterface $urlGenerator
     */
    public function __construct( protected readonly UrlGeneratorInterface $urlGenerator ) {}

    public function getFunctions() : array
    {
        return [
                'url'  => $this->generateRouteUrl( ... ),
                'path' => $this->generateRoutePath( ... ),
        ];
    }
}