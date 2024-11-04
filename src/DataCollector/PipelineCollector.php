<?php

namespace Core\DataCollector;

use Override;
use Symfony\Bundle\FrameworkBundle\DataCollector\AbstractDataCollector;
use Throwable;
use Symfony\Component\HttpFoundation\{Request, Response};

final class PipelineCollector extends AbstractDataCollector
{
    #[Override]
    public static function getTemplate() : ?string
    {
        return '@Core/profiler/pipeline.html.twig';
    }

    #[Override]
    public function collect( Request $request, Response $response, ?Throwable $exception = null ) : void
    {
        $requestAssets = $request->headers->get( 'X-Request-Assets', 'core, document' );
        if ( $requestAssets ) {
            $requestAssets = \explode( ',', $requestAssets );
        }

        $this->data = [
            'assets' => [
                'request'  => $requestAssets,
                'response' => $response,
                'document' => ['core', 'document'],
            ],
        ];
    }

    public function getAllAssets() : int
    {
        return \count( $this->data['assets']['document'] ?? [] );
    }
}
