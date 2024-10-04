<?php

declare(strict_types=1);

namespace Core\Response;

use Core\Service\CurrentRequest;
use Northrook\Clerk;
use Closure;
use Symfony\Component\HttpFoundation\Response;
use const HTTP\OK_200;
use InvalidArgumentException;

final class ResponseHandler
{
    protected ?string $template = null;

    protected ?string $content = null;

    /**
     * @param Document       $document
     * @param Parameters     $parameters
     * @param CurrentRequest $request
     * @param Closure        $lazyLatte
     */
    public function __construct(
        public readonly Document        $document,
        public readonly Parameters      $parameters,
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
     * @param string               $template
     * @param array<string, mixed> $parameters
     *
     * @return Response
     */
    public function template( string $template, array $parameters = [] ) : Response
    {
        $this->template = \str_ends_with( $template, '.latte' )
                ? $template
                : throw new InvalidArgumentException( "The '{$template}' string is not valid.\nIt should end with '.latte' and point to a valid template file.}'" );

        dump( $this->template );

        return $this->response( $template );
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
        Clerk::stopGroup( 'controller' );
        return $response;
    }
}
