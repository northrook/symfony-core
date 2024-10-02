<?php

namespace Core\Controller\Trait;

use Northrook\Logger\Log;
use Northrook\Resource\URL;
// use Northrook\Symfony\Core\Exception\ErrorEventException;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 * @used-by CoreController
 *
 * @author  Martin Nielsen <mn@northrook.com>
 */
trait ResponseMethods
{
    /**
     * Returns a RedirectResponse to the given URL.
     *
     * @param non-empty-string|URL $route
     * @param array                $parameters
     * @param int                  $status     [302] The HTTP status code
     *
     * @return RedirectResponse
     */
    protected function redirectResponse(
            string|URL $route,
            array      $parameters = [],
            int        $status = 302,
    ) : RedirectResponse {

        $url = \is_string( $route ) ? new URL( $route ) : $route;

        // If it ain't guaranteed URL, check if it could be a route string
        if ( ! $url->exists ) {
            $this->throwNotFoundException(  );
        }
        return new RedirectResponse( $url->path, $status );
    }

    /**
     * Returns a RedirectResponse to the given route with the given parameters.
     *
     * @param string $route
     * @param array  $parameters
     * @param int    $status     The HTTP status code (302 "Found" by default)
     *
     * @return RedirectResponse
     */
    final protected function redirectToRoute(
            string $route,
            array  $parameters = [],
            int    $status = Response::HTTP_FOUND,
    ) : RedirectResponse {
        try {
            $url = URL::get( $route, $parameters );
            Log::info( '{controller} is redirecting to {url}', ['controller' => $this::class, 'url' => $url] );
            return new RedirectResponse( $url, $status );
        }
        catch ( Exception $exception ) {
            throw new ErrorEventException( previous : $exception );
        }
    }

    /**
     * Returns a JsonResponse using the {@see SerializerInterface} if available.
     *
     * - Will use any {@see SerializerInterface} assigned to {@see static::serializer}.
     * - Pass a {@see SerializerInterface} as the last argument to override the default.
     * - If no serializer is available, `json_encode` will be used.
     *
     * @param mixed                    $data
     * @param int                      $status
     * @param array                    $headers
     * @param array                    $context
     * @param null|SerializerInterface $serializer
     *
     * @return JsonResponse
     */
    protected function jsonResponse(
            mixed                $data,
            int                  $status = Response::HTTP_OK,
            array                $headers = [],
            array                $context = [],
            ?SerializerInterface $serializer = null,
    ) : JsonResponse {
        if ( null === $serializer
             && \property_exists( $this, 'serializer' )
             && $this->serializer instanceof SerializerInterface
        ) {
            $serializer = $this->serializer;
        }

        if ( $serializer ) {
            $context = \array_merge( ['json_encode_options' => JsonResponse::DEFAULT_ENCODING_OPTIONS], $context );
            $json    = $serializer->serialize( $data, 'json', $context );

            return new JsonResponse( $json, $status, $headers, true );
        }

        return new JsonResponse( $data, $status, $headers );
    }

    /**
     * Return {@see File} object with original or customized
     *  file name and disposition header.
     *
     * @param SplFileInfo|string $file
     * @param ?string            $fileName
     * @param string             $disposition
     *
     * @return BinaryFileResponse
     */
    protected function fileResponse(
            SplFileInfo|string $file,
            ?string            $fileName = null,
            string             $disposition = ResponseHeaderBag::DISPOSITION_ATTACHMENT,
    ) : BinaryFileResponse {
        $response = new BinaryFileResponse( $file );
        $fileName ??= $response->getFile()->getFilename();

        return $response->setContentDisposition( $disposition, $fileName );
    }
}